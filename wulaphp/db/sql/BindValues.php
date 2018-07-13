<?php

namespace wulaphp\db\sql;

/**
 * bind values for Pdo PrepareStatement.
 *
 * @author guangfeng.ning
 *
 */
class BindValues implements \IteratorAggregate {
	private $names  = [];
	private $values = [];

	/**
	 * 将值添加到绑定中.
	 *
	 * @param string $field
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function addValue(string $field, $value): string {
		$rfield = str_replace('`', '', $field);
		$field  = Condition::safeField($field);
		$index  = isset ($this->names [ $field ]) ? $this->names [ $field ] : 0;
		$key    = ':' . $field . '_' . $index;
		if (is_string($value)) {//for bigint
			$type = \PDO::PARAM_STR;
		} else if (is_numeric($value)) {// for int float
			$type = \PDO::PARAM_INT;
		} else if (is_bool($value)) {
			$type  = \PDO::PARAM_INT;
			$value = intval($value);
		} else if (is_null($value)) {
			$type = \PDO::PARAM_NULL;
		} else {
			$type = \PDO::PARAM_STR;
		}
		$this->values []        = array($key, $value, $type, $field, $rfield);
		$this->names [ $field ] = ++$index;

		return $key;
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator ($this->values);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has(string $name): bool {
		$field = Condition::safeField($name);

		return isset ($this->names [ $field ]);
	}

	public function __toString(): string {
		$valString = array();
		foreach ($this->values as $val) {
			$valString [] = $val [0] . ' = ' . $val [1] . ' [' . $val [2] . ']';
		}

		return implode('; ', $valString);
	}
}