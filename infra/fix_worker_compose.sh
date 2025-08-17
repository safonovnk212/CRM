#!/bin/bash

set -e

FILE="docker-compose.override.yml"

# Проверим, существует ли файл
if [[ ! -f $FILE ]]; then
  echo "❌ Файл $FILE не найден в текущей директории: $(pwd)"
  exit 1
fi

# Резервная копия
cp "$FILE" "$FILE.bak.$(date +%s)"

# Обновляем working_dir и healthcheck test путь
sed -i \
  -e 's|working_dir:.*|working_dir: /var/www/html/infra/app|' \
  -e 's|test:.*|test: ["CMD", "test", "-f", "/var/www/html/infra/app/artisan"]|' \
  "$FILE"

echo "✅ Файл $FILE успешно обновлён."
echo "📦 Контейнер worker будет перезапущен..."

# Перезапуск worker
docker compose up -d --force-recreate worker

echo "📄 Последние логи:"
docker compose logs -n 20 worker
