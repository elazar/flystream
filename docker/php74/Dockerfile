FROM php:7.4-cli-alpine

ENV DST_EXT_DIR=/usr/local/lib/php/extensions/no-debug-non-zts-20190902
ENV DST_INI_DIR=/usr/local/etc/php/conf.d

RUN apk update && \
    apk add --no-cache $PHPIZE_DEPS && \
    pecl install xdebug-3.1.6 && \
    docker-php-ext-enable xdebug && \
    echo "xdebug.mode=coverage" > $DST_INI_DIR/50_xdebug.ini

RUN wget -O /tmp/installer -q https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer && cd /tmp && php installer --quiet --install-dir=/usr/local/bin --filename=composer
RUN composer self-update
