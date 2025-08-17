#!/bin/sh
# robust worker healthcheck: проверяем, что процесс жив и Redis отвечает
set -eu

echo "[hc] checking queue:work process..." 1>&2
# ps есть в любом базовом образе; избегаем pgrep
ps aux | grep -q "[a]rtisan queue:work" || { echo "[hc] queue:work process not found"; exit 2; }

echo "[hc] ping redis via Laravel runtime..." 1>&2
php /var/www/html/docker/health/redis.php
rc=$?
if [ $rc -ne 0 ]; then
  echo "[hc] redis ping failed, rc=$rc" 1>&2
  exit $rc
fi

echo "[hc] ok" 1>&2
exit 0
