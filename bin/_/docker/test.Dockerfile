FROM wulaphp/php:7.4-dev

ENV APP_MODE=test APP_DEBUG=100

ADD unittest.tar.bz2 /var/www/html/

RUN cd /var/www/html && mkdir -p storage/logs && mkdir -p storage/tmp &&\
    chown -R www-data:www-data storage;\
    chown -R www-data:www-data /var/lib/nginx;\
    [ -e conf/.env ] && rm conf/.env || echo '.env not found';

VOLUME /var/www/html/tests/reports

COPY etc/ /usr/local/etc/