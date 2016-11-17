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
	 * @param string                         $table
	 * @param \wulaphp\db\DatabaseConnection $db
	 */
	public function __construct($table, DatabaseConnection $db = null) {
		$this->table = $table;
		if (empty($table)) {
			throw new \InvalidArgumentException('$table is invalide');
		}
		parent::__construct($db);
	}
}