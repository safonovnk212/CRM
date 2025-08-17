#!/usr/bin/env bash
set -euo pipefail
cd /opt/leadgateway/infra
TS=$(date +%F_%H%M)
mkdir -p backups
docker compose exec -T mysql sh -lc '
  set -e
  export MYSQL_PWD="$MYSQL_PASSWORD"
  exec mysqldump \
    --single-transaction --quick \
    --routines --triggers --events \
    --set-gtid-purged=OFF \
    --no-tablespaces \
    -u"$MYSQL_USER" "$MYSQL_DATABASE"
' | gzip -9 > "backups/leadgateway_${TS}.sql.gz"
# ротация старше 7 дней
find backups -type f -name "leadgateway_*.sql.gz" -mtime +7 -delete
