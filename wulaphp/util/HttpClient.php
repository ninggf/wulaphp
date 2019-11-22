<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;
/**
 * Http客户端，curl库的深度封装。
 *
 * @package wulaphp\util
 */
class HttpClient extends CurlClient {

    public function __construct(int $timeout = 30) {
        parent::__construct($timeout);
    }
}