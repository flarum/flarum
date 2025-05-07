FROM php:8.1-fpm-alpine

RUN apk add --no-cache bash libzip-dev oniguruma-dev autoconf g++ make sqlite sqlite-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath sockets opcache pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/flarum

# 指定安装 Flarum 1.8.1 版本
RUN composer create-project flarum/flarum . "1.8.1" --no-dev --prefer-dist

# 安装兼容 1.8.1 的简体中文语言包
RUN composer require flarum-lang/chinese-simplified:^1.0

# 安装兼容 1.8.1 的 TNTSearch 扩展
RUN composer require clarkwinkelmann/flarum-ext-scout:^1.0 teamtnt/laravel-scout-tntsearch-driver:^8.7

RUN php flarum cache:clear || true

RUN chown -R www-data:www-data /var/www/flarum

EXPOSE 9000

CMD ["php-fpm"]
