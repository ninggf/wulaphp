<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace m1\classes;

trait M1Prefix {
    public static function urlGroup() {
        return ['~', 'admin'];
    }
}