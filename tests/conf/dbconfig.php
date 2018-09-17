<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$dbcfg = new \wulaphp\conf\DatabaseConfiguration('default');

$dbcfg->driver('MySQL');
$dbcfg->host('127.0.0.1');
$dbcfg->user('root');
$dbcfg->dbname('wula_db');
$dbcfg->encoding('UTF8MB4');
$dbcfg->password('888888');

return $dbcfg;