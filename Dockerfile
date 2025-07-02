# Stage 1: dependencies verzamelen
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-interaction
COPY . .

# Stage 2: runtime
FROM php:8.3-fpm-alpine
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev zip icu-data-full && \
    docker-php-ext-install pdo pdo_mysql gd intl zip

WORKDIR /var/www
COPY --from=vendor /app /var/www
RUN chown -R www-data:www-data /var/www
CMD ["php-fpm"]