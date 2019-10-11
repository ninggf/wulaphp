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
 * for apply_filter call.
 *
 * @package wulaphp\hook
 */
abstract class Alter {
    /**
     * 需要参数个数.
     *
     * @var int
     */
    protected $acceptArgs = 1;
    /**
     * 优先级.
     *
     * @var int
     */
    protected $priority = 10;

    /**
     * 优先级.
     *
     * @return int
     */
    public final function getPriority(): int {
        return $this->priority;
    }

    /**
     * 需要参数个数.
     *
     * @return int
     */
    public final function getAcceptArgs(): int {
        return $this->acceptArgs;
    }

    /**
     * 修改.
     *
     * @param mixed $value
     * @param mixed $args
     *
     * @return mixed 修改后的值
     */
    public abstract function alter($value, ...$args);
}