FROM wulaphp/php:7.4.9-alphine

ARG APP_VER

ENV APP_VER=$APP_VER APP_MODE=pro APP_DEBUG=200

ADD app-$APP_VER.tar.bz2 /var/www/html/

RUN cd /var/www/html && mkdir -p storage/logs && mkdir -p storage/tmp;\
    [ -e conf/.env ] && rm conf/.env || echo '.env not found';\
    chown -R www-data:www-data storage;

VOLUME /var/www/html/storage/logs

STOPSIGNAL SIGTERM

CMD ["php","/var/www/html/artisan","service","start","-f"]
