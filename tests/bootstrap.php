<?php
/*
 * This file is part of wulaphp.
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

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    include APPROOT . '../vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}
