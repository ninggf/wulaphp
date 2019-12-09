<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'host'       => env('redis.host', '127.0.0.1'),
    'port'       => env('redis.port', 6379),
    'db'         => env('redis.db', 8),
    'auth'       => env('redis.auth', ''),
    'timeout'    => env('redis.timeout', 15),
    'persistent' => env('redis.persistent', false)
];