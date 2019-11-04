FROM php:7.3-fpm

WORKDIR /app

RUN apt update \
    && apt install -y libicu-dev libzip-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /app/var \
    && docker-php-ext-install pdo pdo_mysql

COPY data/docker/fpm/php.ini /usr/local/etc/
RUN curl https://getcomposer.org/installer | php -- --filename composer --install-dir=/usr/local/bin

COPY bin bin/
COPY config config/
COPY src src/
COPY public public/
COPY vendor vendor/
COPY composer.* ./

