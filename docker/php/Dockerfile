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
    && pecl install xdebug \
    && rm -f /usr/local/etc/php/conf.d/*xdebug*.ini \
    && docker-php-ext-enable xdebug \
    && mkdir -p /tmp/sessions \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY conf.d/app.dev.ini /usr/local/etc/php/conf.d/zz-app.dev.ini
COPY conf.d/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY fpm.d/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY app-entrypoint.sh /usr/local/bin/app-entrypoint.sh

RUN chmod +x /usr/local/bin/app-entrypoint.sh

WORKDIR /var/www/tracker-api
