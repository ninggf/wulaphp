<?php

namespace wulaphp\db;
/**
 * 简单表.
 *
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 * @method static SimpleTable user()
 */
class SimpleTable extends Table {
	/**
	 * SimpleTable constructor.
	 *
	 * @param string                          $table
	 * @param string|array|DatabaseConnection $db
	 */
	public function __construct($table, $db = null) {
		$this->table = $table;
		if (empty($table)) {
			throw new \InvalidArgumentException('$table is empty');
		}
		parent::__construct($db);
		$this->alias(str_replace('_', '', ucwords($table, '_')));
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return \wulaphp\db\SimpleTable
	 */
	public static function __callStatic($name, $arguments) {
		return new SimpleTable($name, $arguments ? $arguments[0] : null);
	}
}