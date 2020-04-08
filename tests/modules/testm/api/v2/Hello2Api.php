<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\api\v2;

use wulaphp\restful\API;

class Hello2Api extends API {
    /**
     * 打招呼API
     *
     * @apiName Greeting
     *
     * @param string $name (required) 姓名
     *
     * @paramo  string greeting 招呼信息
     *
     * @error   5001 => 演示的错误用的
     *
     * @return array {
     *  "greeting":"Hello Leo"
     * }
     */
    public function greeting($name) {
        return ['greeting' => 'Hello2 ' . $name];
    }
}