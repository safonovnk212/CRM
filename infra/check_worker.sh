#!/bin/bash

cd /opt/leadgateway/infra || exit 1

echo "⏳ Ожидание 10 секунд, пока worker стартует..."
sleep 10

echo "📋 Статус контейнеров:"
docker compose ps

echo "📄 Последние логи worker:"
docker compose logs --tail=30 worker
