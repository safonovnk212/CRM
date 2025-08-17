#!/usr/bin/env bash
set -euo pipefail

echo "== A0: Контейнеры =="
docker compose ps

PHP_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="php"{print $1; exit}')"
WORKER_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="worker"{print $1; exit}')"
echo "PHP_CTN=${PHP_CTN:-<none>}  WORKER_CTN=${WORKER_CTN:-<none>}"
[ -n "${PHP_CTN:-}" ] || { echo "php-контейнер не найден"; exit 2; }

echo
echo "== A1: Laravel/PHP версия и .env ключи (без секретов) =="
docker exec -i "$PHP_CTN" bash -lc 'php artisan --version; php -v | sed -n "1p"; grep -E "^(APP_ENV|APP_URL|DB_CONNECTION|DB_HOST|DB_DATABASE|CACHE_DRIVER|QUEUE_CONNECTION|SESSION_DRIVER|REDIS_HOST|REDIS_PORT)=" .env || true'

echo
echo "== A2: Роут /api/intake в таблице маршрутов =="
docker exec -i "$PHP_CTN" bash -lc 'php artisan route:list --path=api/intake || true'

echo
echo "== A3: Наличие файлов эндпойнта и модели =="
docker exec -i "$PHP_CTN" bash -lc '
for f in \
  app/Models/Lead.php \
  app/Http/Requests/IntakeRequest.php \
  app/Jobs/ProcessLead.php \
  app/Http/Controllers/IntakeController.php \
  routes/api.php
do
  if [ -f "$f" ]; then echo "[OK] $f"; else echo "[MISS] $f"; fi
done
'

echo
echo "== A4: Схема БД через Laravel Schema (leads) =="
docker exec -i "$PHP_CTN" bash -lc 'php -r "
require \"vendor/autoload.php\";
\$app = require \"bootstrap/app.php\";
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
use Illuminate\\Support\\Facades\\DB;
\$cols = DB::select(\"SHOW COLUMNS FROM leads\");
foreach (\$cols as \$c) { echo \$c->Field.\"\\n\"; }
"'

echo
echo "== A5: Проверка, что используется extra (json) и status в модели =="
docker exec -i "$PHP_CTN" bash -lc "grep -n 'protected \\$fillable' -n app/Models/Lead.php && sed -n '1,200p' app/Models/Lead.php | grep -E '\"extra\"|\"status\"|casts' -n || true"

echo
echo "== A6: Redis доступность из приложения =="
docker exec -i "$PHP_CTN" bash -lc 'php -r "require \"vendor/autoload.php\"; \$app=require \"bootstrap/app.php\"; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); try { echo (string)Illuminate\\Support\\Facades\\Redis::ping().\"\\n\"; } catch (Throwable \$e){ echo \"Redis FAIL: \".\$e->getMessage().\"\\n\"; exit(3);} "'

echo
echo "== A7: Состояние воркера и последние логи =="
docker compose ps | awk "NR==1 || /\\bworker\\b/"
docker compose logs --tail=50 worker || true

echo
echo "== A8: Тестовый POST на /api/intake (ожидаем 202 и {status: queued}) =="
RAND=$RANDOM
RESP=$(curl -sS -o /tmp/resp.json -w "%{http_code}" -k -X POST https://localhost/api/intake \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Audit-$RAND\",\"phone\":\"+4860000$RAND\",\"click_id\":\"audit-$RAND\",\"offer_id\":\"42\"}")
echo "HTTP: $RESP"
echo "Body:"; cat /tmp/resp.json; echo

echo
echo "== A9: Проверим, что лид записан (последняя запись) и отдан в очередь =="
docker exec -i "$PHP_CTN" bash -lc 'php -r "
require \"vendor/autoload.php\";
\$app = require \"bootstrap/app.php\";
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
use App\\Models\\Lead;
\$lead = Lead::orderBy(\"id\",\"desc\")->first();
if (\$lead) {
  echo \"last_lead_id=\".\$lead->id.\" phone=\".\$lead->phone.\" status=\".(\$lead->status ?? \"<null>\").\"\\n\";
} else { echo \"no leads\\n\"; }
"'

echo
echo "== A10: По логам воркера ищем обработку ('ProcessLead job handled') =="
docker compose logs --since=3m worker | grep -n "ProcessLead job handled" || true

echo
echo "== A11: Failed jobs (ожидаем пусто) =="
docker exec -i "$PHP_CTN" bash -lc 'php artisan queue:failed || true'
