<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db;

use wulaphp\db\sql\MetaQuery;

/**
 * HasMeta
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 * @date    2021-06-12 11:14:45
 * @since   1.0.0
 * @method \wulaphp\db\View alias(?string $alias)
 */
trait HasMeta {
    /**
     * @param string[] $meta
     *
     * @return \wulaphp\db\sql\MetaQuery
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:34:49
     * @since  1.0.0
     */
    public function withMeta(string ...$meta): MetaQuery {
        return new MetaQuery($this->alias('MT'), ...$meta);
    }
}