<?php
namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;

/**
 * use this class to build SQL where sub-statement.<br/>
 * simple usage:<br/>
 * $con = new Condition();<br/>
 * <ul>
 * <li> and : $con['field [op]'] = condition;</li>
 * </ul>
 *
 *
 * @author guangfeng.ning
 *
 */
class Condition implements \ArrayAccess, \Countable {

	private $conditions = array();

	private $uniques = array();

	public function __construct($con = array()) {
		if ($con && is_array($con)) {
			foreach ($con as $key => $value) {
				$this->conditions [] = array($key, $value);
			}
		}
	}

	public static function safeField($field) {
		return str_replace(array('`', '.'), array('', '_'), $field);
	}

	public static function cleanField($field) {
		if ('*' == $field) {
			return $field;
		}
		$strings = explode('.', $field);
		$fields  = array();
		foreach ($strings as $str) {
			$strs = preg_split('#\bas\b#i', $str);
			$fs   = array();
			foreach ($strs as $s) {
				if ($s == '*') {
					$fs [] = '*';
				} else {
					$fs [] = '`' . trim(trim(trim($s), '`')) . '`';
				}
			}
			$fields [] = implode(' AS ', $fs);
		}

		return implode('.', $fields);
	}

	public function count() {
		return count($this->conditions);
	}

	/**
	 * get the where sql。
	 *
	 * @param DatabaseDialect $dialect
	 * @param BindValues      $values
	 *
	 * @return string
	 */
	public function getWhereCondition(DatabaseDialect $dialect, $values) {
		/*
		 * || - or
		 * @  - exist
		 * !@ - not exist
		 * $ - null or not null
		 * ~ - 正则匹配,
		 * !~ - 正则不匹配。
		 */
		$cons = array();
		foreach ($this->conditions as $con) {
			list ($filed, $value) = $con;
			if (strpos($filed, '||') === 0) {
				$cons [] = 'OR';
				$filed   = substr($filed, 2);
			} else {
				$cons [] = 'AND';
			}
			$filed = trim($filed);
			if ($filed == '@' || $filed == '!@') { // exist or not exist
				$vls   = is_array($value) ? $value : array($value);
				$consx = array();
				foreach ($vls as $value) {
					if ($value instanceof Query) {
						$value->setBindValues($values);
						$value->setDialect($dialect);
						$consx [] = str_replace(array('!', '@'), array('NOT ', 'EXISTS'), $filed) . ' (' . $value->__toString() . ')';
					}
				}
				if (empty ($consx)) {
					array_shift($cons);
				} else {
					$cons [] = implode(' AND ', $consx);
				}
			} else if (empty ($filed) || is_numeric($filed)) { // the value must be a Condition instance.
				if ($value instanceof Condition) {
					$cons [] = '(' . $value->getWhereCondition($dialect, $values) . ')';
				} else if (is_array($value)) {
					$value   = new Condition ($value);
					$cons [] = '(' . $value->getWhereCondition($dialect, $values) . ')';
				} else {
					array_shift($cons);
				}
			} else { // others
				$ops = explode(' ', $filed);
				if (count($ops) == 1) {
					$filed = $ops [0];
					$op    = '=';
				} else {
					$op    = array_pop($ops);
					$filed = implode(' ', $ops);
				}
				$op    = strtoupper(trim($op));
				$filed = Condition::cleanField($filed);
				if ($op == '$') { // null or not null
					if (is_null($value)) {
						$cons [] = $filed . ' IS NULL';
					} else {
						$cons [] = $filed . ' IS NOT NULL';
					}
				} else if ($op == 'BETWEEN' || $op == 'BT' || $op == '!BT' || $op == '!BETWEEN') { // between
					$op      = str_replace('!', 'NOT ', $op);
					$val1    = $values->addValue($filed, $value [0]);
					$val2    = $values->addValue($filed, $value [1]);
					$cons [] = $filed . ' ' . $op . ' ' . $val1 . ' AND ' . $val2;
				} else if ($op == 'IN' || $op == '!IN') { // in
					$op = str_replace('!', 'NOT ', $op);
					if ($value instanceof Query) { // sub-select as 'IN' or 'NOT IN' values.
						$value->setBindValues($values);
						$value->setDialect($dialect);
						$cons [] = $filed . ' ' . $op . ' (' . $value->__toString() . ')';
					} else if (is_array($value)) {
						$vs = array();
						foreach ($value as $v) {
							$vs [] = $values->addValue($filed, $v);
						}
						$cons [] = $filed . ' ' . $op . ' (' . implode(',', $vs) . ')';
					} else if ($value instanceof ImmutableValue) {
						$value->setDialect($dialect);
						$cons [] = $filed . ' ' . $op . ' (' . $dialect->sanitize($value->__toString()) . ')';
					} else {
						array_shift($cons);
					}
				} else if ($op == 'LIKE' || $op == '!LIKE') { // like
					$op      = str_replace('!', 'NOT ', $op);
					$cons [] = $filed . ' ' . $op . ' ' . $values->addValue($filed, $value);
				} else if ($op == 'MATCH') {
					$cons [] = "MATCH({$filed}) AGAINST (" . $values->addValue($filed, $value) . ')';
				} else if ($op == '~' || $op == '!~') {
					$op      = str_replace(array('!', '~'), array('NOT ', 'REGEXP'), $op);
					$cons [] = $filed . ' ' . $op . ' ' . $values->addValue($filed, $value);
				} else {
					if ($value instanceof ImmutableValue) {
						$value->setDialect($dialect);
						$val = $dialect->sanitize($value->__toString());
					} else if ($value instanceof Query) {
						$value->setBindValues($values);
						$value->setDialect($dialect);
						$val = '(' . $value->__toString() . ')';
					} else {
						$val = $values->addValue($filed, $value);
					}
					$cons [] = $filed . ' ' . $op . ' ' . $val;
				}
			}
		}
		if ($cons) {
			array_shift($cons);

			return implode(' ', $cons);
		}

		return '';
	}

	public function offsetExists($offset) {
		return false;
	}

	public function offsetGet($offset) {
		return null;
	}

	/**
	 * || - or
	 * @ - existi
	 * !@ - not exist
	 * $ - null or not null
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		if (is_string($value) || is_numeric($value)) {
			$key = md5(trim($offset) . $value);
			if (isset ($this->uniques [ $key ])) {
				return;
			}
			$this->uniques [ $key ] = 1;
		}
		$this->conditions [] = array($offset, $value);
	}

	public function offsetUnset($offset) {
	}
}