FROM php:fpm-alpine
RUN apk add libjpeg-turbo-dev libpng-dev freetype-dev
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql