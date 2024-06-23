FROM php:8.3-fpm-alpine

RUN apk add --update supervisor

RUN apk add --no-cache zip libzip-dev
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/application
