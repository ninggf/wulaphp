<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\hook;

abstract class Alter2 extends Alter {
    protected $acceptArgs = 2;

    public final function alter($value, ...$args) {
        return $this->doAlter($value, $args[0]);
    }

    protected abstract function doAlter($value, $arg);
}