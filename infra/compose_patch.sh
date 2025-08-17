set -e
cd /opt/leadgateway/infra
# Резервная копия
cp -a docker-compose.yml docker-compose.yml.bak.$(date +%s)

# Если секции volumes у php нет — добавим; если есть — убедимся, что там есть storage/public
awk '
/^[[:space:]]*php:/ {inphp=1}
inphp && /^[[:space:]]*volumes:/ {havevol=1}
inphp && havevol && /^[[:space:]]*-/ {volseen=1}
{print}
END{
  if (!inphp) exit 0
}
' docker-compose.yml >/dev/null 2>&1 || true

# Простой способ — добавить строки если их нет
grep -qE "^\s*php:\s*$" docker-compose.yml || { echo "Не найден сервис php в compose"; exit 1; }

# Добавим блок volumes, если его нет
python3 - <<'PY' || true
import re,sys
p="docker-compose.yml"
s=open(p,encoding="utf-8").read()
if re.search(r'(?ms)^\s*php:\s*\n(.*?\n)(\s*[a-zA-Z])',s): pass
open(p,"w",encoding="utf-8").write(s)
PY

# Вставим строки монтирования (идемпотентно)
grep -q ' - ./storage:/var/www/html/storage' docker-compose.yml || \
  sed -i '/^\s*php:\s*$/,/^[^ ]/ s#^\(\s*\)volumes:\s*$#\0\n\1  - ./storage:/var/www/html/storage#' docker-compose.yml

grep -q ' - ./public:/var/www/html/public' docker-compose.yml || \
  sed -i '/^\s*php:\s*$/,/^[^ ]/ s#^\(\s*\)volumes:\s*$#\0\n\1  - ./public:/var/www/html/public#' docker-compose.yml

# Если блока volumes не было совсем — добавим его с двумя строками
if ! awk '/^\s*php:\s*$/{f=1} f&&/^\s*volumes:/{print; exit}' docker-compose.yml >/dev/null; then
  sed -i '/^\s*php:\s*$/a\  volumes:\n    - ./storage:/var/www/html/storage\n    - ./public:/var/www/html/public' docker-compose.yml
fi
