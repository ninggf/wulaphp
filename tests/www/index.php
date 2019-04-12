<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use wulaphp\app\App;

define('WWWROOT', __DIR__ . DIRECTORY_SEPARATOR);
include WWWROOT . '../bootstrap.php';
return App::run();