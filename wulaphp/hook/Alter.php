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
    protected $acceptArgs = 1;
    protected $priority   = 10;

    public final function getPriority(): int {
        return $this->priority;
    }

    public final function getAcceptArgs(): int {
        return $this->acceptArgs;
    }

    /**
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    public abstract function alter($value, ...$args);
}