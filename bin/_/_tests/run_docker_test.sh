#!/usr/bin/env sh

cnt=0
echo "waiting database ..."
while [ $cnt -le 60 ] ; do
    dbok=$(php -r "try{new PDO('mysql:dbname=mysql;host=mysql','root','888888');echo 'ok';}catch(Exception \$e){echo \$e->getMessage();}")
    if [ "$dbok" = "ok" ]; then
        break
    fi
    sleep 1
    cnt=$(( cnt+1 ))
done

if [ $cnt -le 60 ]; then
    echo "database is ready!"
    echo ""
    php ./vendor/bin/phpunit --prepend ./bootstrap.php -c tests/phpunit.xml.dist --colors=always --testdox
else
    echo "Could not connect to the database server!"
    exit 1
fi