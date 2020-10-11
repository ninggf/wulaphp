#!/usr/bin/env sh

cnt=0
while [ $cnt -le 60 ] ; do
    dbok=$(php -r "include 'bootstrap.php';try{\$db=\wulaphp\app\App::db();echo \$db?'ok':'no';}catch(Exception \$e){echo 'no';}")
    if [ "$dbok" = "ok" ]; then
        break
    fi
    echo "database is not ready: $cnt"
    sleep 1
    cnt=$(( cnt+1 ))
done

if [ $cnt -le 60 ]; then
    php ./vendor/bin/phpunit --prepend ./bootstrap.php -c tests/phpunit.xml.dist --colors=always --testdox \
    --testdox-text ./reports/testdox.txt --testsuite all
else
    echo "Could not connect to the database server!"
    exit 1
fi