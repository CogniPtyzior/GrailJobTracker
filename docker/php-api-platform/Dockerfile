FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    postgresql-client \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    && pecl install xdebug opentelemetry \
    && rm -f /usr/local/etc/php/conf.d/*xdebug*.ini \
    && docker-php-ext-enable xdebug opentelemetry \
    && mkdir -p /tmp/sessions \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/conf.d/app.dev.ini /usr/local/etc/php/conf.d/zz-app.dev.ini
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY docker/php/fpm.d/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh

RUN chmod +x /usr/local/bin/app-entrypoint.sh

WORKDIR /var/www/tracker-api-platform

COPY TrackerApiPlatform/composer.json TrackerApiPlatform/composer.lock ./

RUN composer install --prefer-dist --no-interaction --no-scripts

COPY TrackerApiPlatform/ ./

RUN mkdir -p var/cache var/log var/sessions var/runtime \
    && chown -R www-data:www-data var public \
    && chmod -R ug+rwX var