FROM php:8.3-fpm-alpine

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apk update && apk --no-cache add autoconf \
    $PHPIZE_DEPS \
    postgresql-dev \
    libzip-dev \
    freetype \
    libpng \
    libjpeg-turbo \
    freetype-dev \
    libpng-dev \
    jpeg-dev \
    libjpeg \
    libjpeg-turbo-dev \
    php-xml \
    php-json \
    php-curl \
    php-zip \
    icu-dev \
    linux-headers

RUN docker-php-ext-configure gd --with-freetype --with-jpeg\
    && docker-php-ext-configure zip

RUN docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd zip

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug


COPY ./docker/xdebug/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
