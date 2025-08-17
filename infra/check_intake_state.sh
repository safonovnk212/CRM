#!/usr/bin/env bash
set -euo pipefail

echo "== CHECK 0: Docker services & container names =="
docker compose ps
PHP_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="php"{print $1; exit}')"
WORKER_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="worker"{print $1; exit}')"
CADDY_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="caddy"{print $1; exit}')"
REDIS_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="redis"{print $1; exit}')"
MYSQL_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2~/mysql|mariadb|db/{print $1; exit}')"
echo "PHP_CTN=${PHP_CTN:-<none>}  WORKER_CTN=${WORKER_CTN:-<none>}  CADDY_CTN=${CADDY_CTN:-<none>}  REDIS_CTN=${REDIS_CTN:-<none>}  DB_CTN=${MYSQL_CTN:-<none>}"
[ -n "${PHP_CTN:-}" ] || { echo "!! php-контейнер не найден"; exit 2; }

echo
echo "== CHECK 1: Laravel inside php container =="
docker exec -it "$PHP_CTN" bash -lc 'ls -la | sed -n "1,5p"; php artisan --version'

echo
echo "== CHECK 2: .env ключевые параметры (без секретов) =="
docker exec -it "$PHP_CTN" bash -lc '
  if [ -f .env ]; then
    grep -E "^(APP_ENV|QUEUE_CONNECTION|CACHE_DRIVER|REDIS_HOST|DB_HOST|DB_DATABASE|DB_CONNECTION)=" .env || true
  else
    echo ".env отсутствует";
  fi
'

echo
echo "== CHECK 3: Redis доступность из Laravel =="
docker exec -it "$PHP_CTN" bash -lc '
php -d detect_unicode=0 -r "
require \"vendor/autoload.php\";
\$app = require \"bootstrap/app.php\";
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  \$pong = Illuminate\\Support\\Facades\\Redis::ping();
  echo \"Redis PING: \".(is_string(\$pong)?\$pong:\"OK\").\"\\n\";
} catch (Throwable \$e) {
  echo \"Redis FAIL: \".\$e->getMessage().\"\\n\";
  exit(3);
}
"
'

echo
echo "== CHECK 4: Наличие файлов эндпойнта =="
docker exec -it "$PHP_CTN" bash -lc '
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
echo "== CHECK 5: Маршрут /api/intake зарегистрирован =="
docker exec -it "$PHP_CTN" bash -lc 'php artisan route:list --path=api/intake || true'

echo
echo "== CHECK 6: Статус миграций (leads и failed_jobs) =="
docker exec -it "$PHP_CTN" bash -lc '
php artisan migrate:status | sed -n "1,200p"
echo "-- grep по ключевым миграциям --"
php artisan migrate:status | grep -E "create_leads_table|failed_jobs" || true
'

echo
echo "== CHECK 7: Есть ли таблица leads в БД =="
docker exec -it "$PHP_CTN" bash -lc '
php -r "
require \"vendor/autoload.php\";
\$app = require \"bootstrap/app.php\";
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
use Illuminate\\Support\\Facades\\Schema;
echo Schema::hasTable(\"leads\") ? \"HAS_TABLE\\n\" : \"NO_TABLE\\n\";
"
'

echo
echo "== CHECK 8: Воркер очередей (docker compose service: worker) =="
docker compose ps | awk "NR==1 || /\\bworker\\b/"
if [ -n "${WORKER_CTN:-}" ]; then
  echo "-- последние 50 строк логов воркера --"
  docker compose logs --tail=50 worker || true
else
  echo "worker-сервис не найден."
fi

echo
echo "== CHECK 9: Очередь по факту (есть ли зависшие/фейлы) =="
docker exec -it "$PHP_CTN" bash -lc '
php artisan queue:list || true
php artisan queue:failed || true
'

echo
echo "== CHECK 10: Быстрый dry-run curl до Caddy (проверка 308/200) =="
curl -sI http://localhost | sed -n "1,5p" || true
