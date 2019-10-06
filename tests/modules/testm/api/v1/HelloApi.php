<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\api\v1;

use wulaphp\restful\API;

class HelloApi extends API {
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
        return ['greeting' => 'Hello ' . $name];
    }

    public function greetingPost($name) {
        return ['greeting' => 'Hello ' . $name];
    }

    public function uploadPost($avatar, $name) {
        @unlink($avatar['tmp_name']);
        if ($avatar['size'] == 5) {
            return ['name' => $name, 'avatar' => $avatar['name']];
        } else {
            return ['error' => 12];
        }
    }

    public function rt() {
        if (extension_loaded('yac')) {
            $rtc = new \wulaphp\cache\YacCache();
        } else if (function_exists('apcu_store')) {
            $rtc = new \wulaphp\cache\ApcCacher();
        } else if (function_exists('xcache_get')) {
            $rtc = new \wulaphp\cache\XCacheCacher();
        } else {
            $rtc = new \wulaphp\cache\Cache();
        }

        return ['file' => $rtc->get('wulaphp\restful\API.class')];
    }
}