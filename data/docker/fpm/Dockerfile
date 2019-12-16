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
RUN apk add bash curl

# Install phpvm
RUN curl -sL https://finwo.github.io/phpvm/install.sh | bash /dev/stdin --prefix /usr

# Setup PHP
RUN phpvm use 7.3.12 --install-dependencies
RUN mkdir -p /var/log/php
RUN addgroup -S -g 1000 www-data
RUN adduser -SD -u 1000 -G www-data www-data
EXPOSE 9000

CMD /usr/local/sbin/php-fpm -F
