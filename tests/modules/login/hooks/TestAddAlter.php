<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\hooks;

use wulaphp\hook\Alter;

class TestAddAlter extends Alter {
    protected $acceptArgs = 3;

    public function alter($value, ...$args) {
        return $value + $args[0] + $args[1];
    }
}
