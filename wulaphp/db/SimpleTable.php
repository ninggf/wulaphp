<?php

namespace wulaphp\db;
/**
 * 简单表.
 *
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 */
class SimpleTable extends Table {
    /**
     * SimpleTable constructor.
     *
     * @param string                          $table
     * @param string|array|DatabaseConnection $db
     *
     * @throws \InvalidArgumentException|\wulaphp\db\DialectException
     */
    public function __construct(string $table, $db = null) {
        if (empty($table)) {
            throw new \InvalidArgumentException('$table is empty');
        }
        $this->table = $table;
        parent::__construct($db);
        $this->alias(str_replace('_', '', ucwords($table, '_')));
    }
}