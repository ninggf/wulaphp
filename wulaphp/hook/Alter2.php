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
/**
 * 2个参数Alter
 *
 * @package wulaphp\hook
 */
abstract class Alter2 extends Alter {
    /**
     * 需要参数个数.
     *
     * @var int
     */
    protected $acceptArgs = 2;

    /**
     * 修改.
     *
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    public final function alter($value, ...$args) {
        return $this->doAlter($value, $args[0]);
    }

    /**
     * 真的修改.
     *
     * @param mixed $value 值
     * @param mixed $arg   参数
     *
     * @return mixed 修改后的值
     */
    protected abstract function doAlter($value, $arg);
}