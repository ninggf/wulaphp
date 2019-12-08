<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db\sql;

/**
 * Class Ref
 * @package wulaphp\db\sql
 * @internal
 */
class Ref extends ImmutableValue {
    public function __toString() {
        return Condition::cleanField($this->value, $this->dialect);
    }
}