FROM php:8.3-fpm-alpine

RUN apk add --update supervisor bash busybox-suid

RUN apk add --no-cache zip libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_mysql

RUN mkdir /etc/cron.d
COPY crontab /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron
RUN crontab /etc/cron.d/laravel-cron
RUN touch /var/log/cron.log

WORKDIR /var/www/application
