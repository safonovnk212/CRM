#!/bin/bash

echo "📁 Переход в директорию проекта..."
cd /opt/leadgateway/infra || {
  echo "❌ Не удалось перейти в /opt/leadgateway/infra"
  exit 1
}

FILE="docker-compose.override.yml"

# 🔒 Бэкап
if [[ -f $FILE ]]; then
  echo "🧾 Создаю резервную копию $FILE..."
  cp "$FILE" "$FILE.bak.$(date +%s)"
else
  echo "❌ Файл $FILE не найден!"
  exit 1
fi

# 🧩 Добавим или заменим healthcheck в блоке caddy
echo "➕ Добавляю healthcheck в существующий блок caddy..."

awk '
BEGIN { in_caddy = 0 }
/^[[:space:]]*caddy:$/ { in_caddy = 1; print; next }
/^[[:space:]]*[a-zA-Z0-9_-]+:$/ { in_caddy = 0; print; next }
in_caddy && /^[[:space:]]*healthcheck:/ { skip = 1 }
in_caddy && skip && /^[[:space:]]+[a-z]+:/ { next }
{
  if (in_caddy && !skip && $0 ~ /^[[:space:]]+[a-zA-Z]+:/) {
    print "    healthcheck:"
    print "      test: [\"CMD\", \"curl\", \"-f\", \"http://localhost\"]"
    print "      interval: 30s"
    print "      timeout: 10s"
    print "      retries: 3"
    print ""
    skip = 1
  }
  print
}
' "$FILE" > "${FILE}.tmp" && mv "${FILE}.tmp" "$FILE"

echo "✅ Healthcheck добавлен в $FILE"

# 🔁 Перезапуск Caddy
echo "🔁 Перезапуск контейнера Caddy..."
docker compose up -d --force-recreate caddy

echo "⏳ Ожидание 10 секунд..."
sleep 10

# 📋 Проверка
echo "📋 Статус контейнера Caddy:"
docker compose ps caddy

echo "📄 Логи Caddy:"
docker compose logs --tail=20 caddy
