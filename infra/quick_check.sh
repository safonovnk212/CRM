#!/usr/bin/env bash
set -euo pipefail
echo "== CHAT-0 QUICK CHECK =="; date
echo "- TZ:"; timedatectl | sed -n '1,6p'
echo "- UFW:"; sudo ufw status | sed -n '1,8p'
echo "- SWAP:"; swapon --show || true; free -h | sed -n '1,3p'
echo "- Docker:"; systemctl is-active docker && docker --version && docker compose version
echo "- Network:"; docker network ls | grep lead_net || echo "lead_net missing"
echo "- Images:"; docker images | grep -E 'caddy|redis|mysql|python' || echo "base images missing"
echo "- Infra files:"; ls -l /opt/leadgateway/infra
