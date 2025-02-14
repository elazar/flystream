FROM php:8.3-cli-alpine

ENV SRC_EXT_DIR=/usr/lib/php83/modules
ENV DST_EXT_DIR=/usr/local/lib/php/extensions/no-debug-non-zts-20230831

ENV SRC_INI_DIR=/etc/php83/conf.d
ENV DST_INI_DIR=/usr/local/etc/php/conf.d

RUN apk update
RUN apk add php83-pecl-xdebug
RUN ln -s $SRC_EXT_DIR/xdebug.so $DST_EXT_DIR/xdebug.so
RUN echo -e "zend_extension=$DST_EXT_DIR/xdebug.so\nxdebug.mode=coverage" > $DST_INI_DIR/50_xdebug.ini

RUN wget -O /tmp/installer -q https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer && cd /tmp && php installer --quiet --install-dir=/usr/local/bin --filename=composer
RUN composer self-update
