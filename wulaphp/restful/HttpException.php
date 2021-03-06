<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\restful;
/**
 * HTTP 异常响应.
 *
 * @package wulaphp\restful
 */
class HttpException extends \Exception {
    public function __construct($message, $code = 500) {
        parent::__construct($message, $code, null);
    }
}