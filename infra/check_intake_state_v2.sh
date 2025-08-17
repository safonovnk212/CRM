#!/usr/bin/env bash
set -euo pipefail

echo "== CHECK 0: Docker services и имена контейнеров =="
docker compose ps
PHP_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="php"{print $1; exit}')"
WORKER_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="worker"{print $1; exit}')"
CADDY_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="caddy"{print $1; exit}')"
REDIS_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2=="redis"{print $1; exit}')"
DB_CTN="$(docker compose ps -a --format 'table {{.Name}}\t{{.Service}}' | awk '$2~/mysql|mariadb|db/{print $1; exit}')"
echo "PHP_CTN=${PHP_CTN:-<none>}  WORKER_CTN=${WORKER_CTN:-<none>}  CADDY_CTN=${CADDY_CTN:-<none>}  REDIS_CTN=${REDIS_CTN:-<none>}  DB_CTN=${DB_CTN:-<none>}"
[ -n "${PHP_CTN:-}" ] || { echo "php-контейнер не найден"; exit 2; }

echo
echo "== CHECK 1: Версия Laravel и PHP =="
docker exec -it "$PHP_CTN" bash -lc 'php artisan --version || true; php -v | sed -n "1p"'

echo
echo "== CHECK 2: .env ключевые параметры =="
docker exec -it "$PHP_CTN" bash -lc '
  if [ -f .env ]; then
    grep -E "^(APP_ENV|APP_URL|QUEUE_CONNECTION|CACHE_DRIVER|SESSION_DRIVER|REDIS_HOST|REDIS_PORT|DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME)=" .env || true
  else
    echo ".env отсутствует"
  fi
'

echo
echo "== CHECK 3: Проверка Redis из Laravel =="
docker exec -it "$PHP_CTN" bash -lc '
php -r "
require \"vendor/autoload.php\";
\$app=require \"bootstrap/app.php\";
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
try {
  \$pong=Illuminate\\Support\\Facades\\Redis::ping();
  echo \"Redis PING: \".(is_string(\$pong)?\$pong:\"OK\").\"\n\";
} catch (Throwable \$e){
  echo \"Redis FAIL: \".\$e->getMessage().\"\n\";
  exit(3);
}
"'

echo
echo "== CHECK 4: Наличие ключевых файлов проекта =="
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
echo "== CHECK 5: Проверка маршрута /api/intake =="
docker exec -it "$PHP_CTN" bash -lc 'php artisan route:list --path=api/intake || true'

echo
echo "== CHECK 6: Миграции (в том числе leads, failed_jobs) =="
docker exec -it "$PHP_CTN" bash -lc '
php artisan migrate:status | sed -n "1,200p"
echo "-- grep ключевых миграций --"
php artisan migrate:status | grep -E "create_leads_table|failed_jobs" || true
'

echo
echo "== CHECK 7: Наличие таблицы leads в БД =="
docker exec -it "$PHP_CTN" bash -lc '
php -r "
require \"vendor/autoload.php\";
\$app=require \"bootstrap/app.php\";
\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
use Illuminate\\Support\\Facades\\Schema;
echo Schema::hasTable(\"leads\")?\"HAS_TABLE\n\":\"NO_TABLE\n\";
"'

echo
echo "== CHECK 8: Состояние worker и последние логи =="
docker compose ps | awk "NR==1 || /\\bworker\\b/"
if [ -n "${WORKER_CTN:-}" ]; then
  docker compose logs --tail=50 worker || true
else
  echo "worker-сервис не найден."
fi

echo
echo "== CHECK 9: Очередь: failed jobs =="
docker exec -it "$PHP_CTN" bash -lc 'php artisan queue:failed || true'

echo
echo "== CHECK 10: Проверка редиректа Caddy с HTTP на HTTPS =="
curl -sI http://localhost | sed -n "1,5p" || true
