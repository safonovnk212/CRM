FROM php:8.3-fpm-alpine

# deps для сборки PECL и php-расширений
RUN apk add --no-cache tzdata bash git libzip-dev oniguruma-dev openssl-dev linux-headers $PHPIZE_DEPS

# стандартные расширения
RUN docker-php-ext-install -j$(nproc) pdo_mysql bcmath opcache

# PECL redis
RUN pecl install -o -f redis && docker-php-ext-enable redis && rm -rf /tmp/pear

# php.ini
RUN { \
  echo "memory_limit=${PHP_MEMORY_LIMIT:-512M}"; \
  echo "upload_max_filesize=${PHP_UPLOAD_LIMIT:-32M}"; \
  echo "post_max_size=${PHP_UPLOAD_LIMIT:-32M}"; \
  echo "date.timezone=${TZ:-UTC}"; \
} > /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/html
