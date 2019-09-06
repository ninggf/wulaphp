<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use app\gearman\ReverseWorker;

include __DIR__ . '/../../../bootstrap.php';

$worker = new ReverseWorker();

$worker->run(false);