#!/usr/bin/env bash
set -euo pipefail

echo "=== TIME & TZ ==="
timedatectl status | sed -n '1,6p'

echo -e "\n=== DISK SPACE ==="
df -h /

echo -e "\n=== MEMORY & SWAP ==="
free -h

echo -e "\n=== UFW STATUS ==="
sudo ufw status || true

echo -e "\n=== DOCKER DAEMON ==="
systemctl is-active docker && docker --version && docker compose version

echo -e "\n=== DOCKER NETWORKS ==="
docker network ls | grep -E 'lead_net|NAME' || true

echo -e "\n=== DOCKER IMAGES (base) ==="
docker images | grep -E 'caddy|redis|mysql|python' || true

echo -e "\n=== INFRA FILES ==="
ls -l /opt/leadgateway/infra
echo -e "\n.env content:"
cat /opt/leadgateway/infra/.env
