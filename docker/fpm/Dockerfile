FROM php:7.3-fpm

WORKDIR /app

RUN apt update \
    && apt install -y libicu-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /app/var

COPY docker/fpm/php.ini /usr/local/etc/

COPY bin bin/
COPY config config/
COPY src src/
COPY public public/
COPY vendor vendor/
COPY composer.* ./

