FROM node:20-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader

FROM php:8.2-cli-alpine

WORKDIR /app

RUN docker-php-ext-install pdo_mysql

COPY . .

COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN php artisan package:discover --ansi

EXPOSE 8040

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8040"]