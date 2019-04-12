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
}