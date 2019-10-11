<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;
/**
 * 环境检测器.
 *
 * @package wulaphp\util
 */
abstract class EnvChecker {
    /**
     * 检测名称.
     *
     * @var string
     */
    public $name;
    /**
     * 期望值.
     *
     * @var string
     */
    public $expectedResult;
    /**
     * 实际值.
     *
     * @var string
     */
    public $actualResult;
    /**
     * 是否通过.
     *
     * @var bool
     */
    public $pass;

    public abstract function check();
}