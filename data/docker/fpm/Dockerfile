FROM alpine:edge

ENV TERM=ansi

WORKDIR /app

RUN echo "" > /etc/apk/repositories
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/main"      >> /etc/apk/repositories
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/community" >> /etc/apk/repositories
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/testing"   >> /etc/apk/repositories

# Update all packages & install bash/curl
RUN apk update
RUN apk upgrade
RUN apk add bash curl git

# Build user to run as
RUN addgroup -g 1000 -S    www-data
RUN adduser  -u 1000 -S -G www-data -h /home/www www-data

# Setup PHP
RUN apk add php7
RUN apk add php7-fpm
RUN apk add php7-json

# Setup composer
RUN apk add php7-iconv
RUN apk add php7-openssl
RUN apk add php7-phar

RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/bin
RUN ln -s /usr/bin/composer.phar /usr/bin/composer

# Install remaining dependencies
COPY data/docker/fpm/docker-ext-install.php /usr/bin/composer-ext-install
RUN chmod +x  /usr/bin/composer-ext-install
COPY composer.json /etc/php/composer.json
COPY composer.lock /etc/php/composer.lock

RUN composer-ext-install /etc/php/composer.json /etc/php/composer.lock

# Setup PHP
RUN mkdir -p /var/log/php


# sudo chown -R www-data:www-data /var/www/$1
CMD /usr/sbin/php-fpm7 -F

EXPOSE 9000

