<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\hooks\test;

use wulaphp\hook\Alter3;

class Add extends Alter3 {

    public function doAlter($value, $arg1, $arg2) {
        return $value + $arg1 + $arg2;
    }
}