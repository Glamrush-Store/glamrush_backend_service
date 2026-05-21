FROM php:8.3-cli-bookworm AS app

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    OCTANE_SERVER=swoole \
    OCTANE_HOST=0.0.0.0 \
    OCTANE_PORT=8000 \
    OCTANE_WORKERS=2 \
    OCTANE_MAX_REQUESTS=500

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
        procps \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_pgsql \
        sockets \
        zip \
    && pecl install redis swoole \
    && docker-php-ext-enable opcache redis swoole \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && groupadd --gid "${GID}" laravel \
    && useradd --uid "${UID}" --gid laravel --shell /bin/bash --create-home laravel \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

USER laravel

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["sh", "-lc", "php artisan octane:start --server=${OCTANE_SERVER:-swoole} --host=${OCTANE_HOST:-0.0.0.0} --port=${OCTANE_PORT:-8000} --workers=${OCTANE_WORKERS:-2} --max-requests=${OCTANE_MAX_REQUESTS:-500}"]
