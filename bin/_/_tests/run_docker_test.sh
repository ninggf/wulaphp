# prepare data for unit test

rm conf/install.lock conf/config.php conf/dbconfig.php

sleep 15 # wait db to startup

php ./artisan wulacms:install -c tests/install.ini

php ./vendor/bin/phpunit --prepend ./bootstrap.php -c tests/phpunit.xml.dist --colors=always --testdox\
 --testdox-text ./storage/reports/testdox.txt --testsuite all