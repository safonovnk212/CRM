#!/bin/bash

echo "🔍 Проверка, всё ли работает..."
sleep 1

cd /opt/leadgateway/infra || {
  echo "❌ Не удалось перейти в директорию"
  exit 1
}

echo "✅ Всё ок, продолжаем..."
sleep 2

echo "�� Скрипт завершён."
