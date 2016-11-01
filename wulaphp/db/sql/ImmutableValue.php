<?php
namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;

/**
 * Immutable value for a value which references to field or a function
 *
 * @author guangfeng.ning
 *
 */
class ImmutableValue {

	private $value;

	private $alias;
	/**
	 * @var DatabaseDialect
	 */
	private $dialect;

	public function __construct($value, $alias = null) {
		$this->value = $value;
		$this->alias = $alias;
	}

	public function setDialect(DatabaseDialect $dialect) {
		$this->dialect = $dialect;
	}

	public function alias($alias) {
		$this->alias = $alias;
	}

	public function __toString() {
		$dialect = $this->dialect;
		if ($dialect == null) {
			$value = $this->value;
		} else {
			$value = trim($dialect->quote($this->value), "'");
		}
		if ($this->alias) {
			return $value . ' AS `' . $this->alias . '`';
		} else {
			return $value;
		}
	}
}