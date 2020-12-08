<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$cnt = 0;
while (true) {
    try {
        new PDO('mysql:dbname=mysql;host=127.0.0.1', 'root', '');
        echo "mysql server connected!\n";
    } catch (Exception $e) {
        $cnt ++;
        if ($cnt > 30) {
            exit(1);
        }
        echo "try connect to mysql server: ", $cnt, "\n";
        sleep(1);
    }
}