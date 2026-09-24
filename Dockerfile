# -----------------------------------------------------------
# Stage 1: Install Composer dependencies
# -----------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# -----------------------------------------------------------
# Stage 2: Production application image
# -----------------------------------------------------------
FROM php:8.3-fpm-alpine

# System dependencies & PHP extensions
RUN apk add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        intl \
    && apk del freetype-dev libjpeg-turbo-dev libpng-dev icu-dev oniguruma-dev

WORKDIR /var/www/html

# Copy application source
COPY --chown=www-data:www-data . .

# Copy vendor from build stage
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Laravel runtime directories
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
