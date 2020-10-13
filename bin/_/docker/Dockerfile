FROM wulaphp/php:7.4-fpm

ARG APP_VER

ENV APP_VER=$APP_VER APP_MODE=pro APP_LOGGER_DRIVER=container APP_LOGGER_LEVEL=info

ADD app-$APP_VER.tar.bz2 /var/www/html/

RUN cd /var/www/html && mkdir -p storage/logs && mkdir -p storage/tmp &&\
    chown -R www-data:www-data storage;\
    chown -R www-data:www-data /var/lib/nginx;\
    [ -e conf/.env ] && rm conf/.env || echo '.env not found';\
    rm -f conf/*_dev.php conf/*_test.php;\
    find ./ -type f -name "*.js" | xargs gzip -7 -k;\
    find ./ -type f -name "*.css" | xargs gzip -7 -k;\
    find ./ -type f -name "*.svg" | xargs gzip -7 -k;\
    find ./ -type f -name "*.html" | xargs gzip -7 -k;\
    for f in $(find ./ -type f -name "*.js.gz"); do\
        mv $f "$f.js";\
    done ;\
    for f in $(find ./ -type f -name "*.css.gz"); do\
        mv $f "$f.css";\
    done ;\
    for f in $(find ./ -type f -name "*.svg.gz"); do\
        mv $f "$f.svg";\
    done;\
    for f in $(find ./ -type f -name "*.html.gz"); do\
        mv $f "$f.html";\
    done

EXPOSE 80

VOLUME /var/www/html/storage/logs

COPY etc/ /usr/local/etc/