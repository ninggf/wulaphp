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
 * for fire call.
 *
 * @package wulaphp\hook
 */
abstract class Handler {
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
     * hook处理.
     *
     * @param mixed $args
     */
    public abstract function handle(...$args);
}