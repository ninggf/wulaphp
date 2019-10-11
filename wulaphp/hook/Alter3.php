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
 * 3个参数的Alter。
 *
 * @package wulaphp\hook
 */
abstract class Alter3 extends Alter {
    /**
     * 需要参数个数.
     *
     * @var int
     */
    protected $acceptArgs = 3;

    /**
     * 修改.
     *
     * @param mixed $value
     * @param mixed $args
     *
     * @return mixed
     */
    public final function alter($value, ...$args) {
        return $this->doAlter($value, $args[0], $args[1]);
    }

    /**
     * 真的修改.
     *
     * @param mixed $value 值
     * @param mixed $arg1  参数1
     * @param mixed $arg2  参数2
     *
     * @return mixed 修改后的值
     */
    protected abstract function doAlter($value, $arg1, $arg2);
}