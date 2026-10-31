# AFTERGLOW - Laravel 13 + Vite on Render
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM php:8.4-apache-bookworm
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libonig-dev libicu-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_pgsql pgsql mbstring intl zip gd bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
       storage/framework/views storage/logs storage/app/private \
       storage/app/public bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction \
       --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

RUN a2enmod rewrite headers \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's@/var/www/html@/var/www/html/public@g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' \
       > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

EXPOSE 10000
# Configure Neon PostgreSQL and Laravel environment variables on Render before deploying.
CMD ["sh", "-c", "php artisan migrate --force && exec apache2-foreground"]
