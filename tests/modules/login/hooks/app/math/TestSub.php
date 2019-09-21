<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\hooks\app\Math;

use wulaphp\hook\Alter2;

class TestSub extends Alter2 {

    public function doAlter($value, $arg) {
        return $value - $arg;
    }
}