<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('DEBUG', 100);

define('ALIAS_ENABLED', true);

define('PUBLIC_DIR', 'www');

define('APPROOT', __DIR__ . DIRECTORY_SEPARATOR);

if (isset($_REQUEST['_PRO_'])) {
    define('APP_MODE', 'pro');
}

if (isset($_REQUEST['_RIC_'])) {
    define('RUN_IN_CLUSTER', true);
}

if (defined('PHPUNIT_COMPOSER_INSTALL')) {
    require APPROOT . '../bootstrap.php';
} else {
    require APPROOT . '../vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}