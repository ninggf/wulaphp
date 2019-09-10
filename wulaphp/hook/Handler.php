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
    private $acceptArgs = 1;
    private $priority   = 10;

    public final function getPriority(): int {
        return $this->priority;
    }

    public final function getAcceptArgs(): int {
        return $this->acceptArgs;
    }

    /**
     * hook处理.
     *
     * @param mixed ...$args
     */
    public abstract function handle(...$args);
}