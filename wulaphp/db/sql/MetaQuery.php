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

use wulaphp\db\View;

/**
 * MetaQuery
 * @package wulaphp\db\sql
 * @author  Leo Ning <windywany@gmail.com>
 * @date    2021-06-12 11:57:14
 * @since   1.0.0
 * @property-read string $metaIdField
 * @property-read string $metaTable
 * @property-read string $metaNameField
 */
class MetaQuery {
    private $view;
    private $alias;
    private $metas;

    /**
     *
     *
     * @param \wulaphp\db\View $view
     * @param string           ...$meta 要查询的元数据
     */
    public function __construct(View $view, string ...$meta) {
        $this->view  = $view;
        $this->alias = $view->alias;
        $this->metas = $meta;
        if (!property_exists($this, 'metaTable')) {
            $this->metaTable = $this->view->originTable . '_meta';
        }
        if (!property_exists($this, 'metaIdField') || !$this->metaIdField) {
            $this->metaIdField = strtolower(preg_replace('/_meta$/', '', $this->view->originTable)) . '_id';
        }
        if (!property_exists($this, 'metaNameField') || !$this->metaNameField) {
            $this->metaNameField = 'name';
        }

    }

    /**
     * 查询
     *
     * @param string ...$fields 主查询字段.
     *
     * @return \wulaphp\db\sql\Query
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-12 11:46:02
     * @since  1.0.0
     */
    public function select(string ...$fields): Query {
        if (empty($fields)) {
            $fields = [$this->alias . '.*'];
        }
        $query = $this->view->select(...$fields);

        foreach ($this->metas as $idx => $meta) {
            $mt    = preg_split('/\s+AS\s+/i', trim($meta));
            $mn    = $mt[0];
            $ma    = $mt[1] ?? $mn;
            $alias = '_MV' . $idx;
            $query->field($alias . '.value', $ma);

            $on = $alias . '.' . $this->metaIdField . '=MT.' . $this->view->primaryKey;
            $on .= ' AND ' . $alias . '.' . $this->metaNameField . " = '$mn'";
            $query->join('{' . $this->metaTable . '} AS ' . $alias, $on);
        }

        return $query;
    }
}