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

	private $conditions = [];

	private $uniques = [];
	private $alias   = null;

	public function __construct($con = [], $alias = null) {
		$this->alias = $alias;
		if ($con && is_array($con)) {
			foreach ($con as $key => $value) {
				$this->offsetSet($key, $value);
			}
		}
	}

	/**
	 * 表单安全字段名.
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	public static function safeField($field) {
		return str_replace(['`', '.'], ['', '_'], $field);
	}

	/**
	 * 数据库安全字段.
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	public static function cleanField($field) {
		if ('*' == $field) {
			return $field;
		}
		$strings = explode('.', $field);
		$fields  = [];
		foreach ($strings as $str) {
			$strs = preg_split('#\bas\b#i', $str);
			$fs   = [];
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
		return $dialect->buildWhereString($this->conditions, $values);
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
		$this->conditions [] = [$offset, $value];
	}

	public function offsetUnset($offset) {
	}

	/**
	 * 解析查询条件.
	 *
	 * @param string $expression
	 * @param array  $defines
	 *
	 * @return array
	 */
	public static function parseSearchExpression($expression, $defines) {
		$where = [];
		if ($expression && $defines && is_array($defines)) {
			$expressions = explode('&&', $expression);
			foreach ($expressions as $exp) {
				if (preg_match("#^'([^']+)'\s+([^\s]+)\s+(.*)#", trim($exp), $ms)) {
					if (isset($defines[ $ms[1] ])) {
						$field           = $defines[ $ms[1] ] . ($ms[2] == '=' ? '' : ' ' . $ms[2]);
						$where[ $field ] = $ms[3];
					}
				}
			}
		}

		return $where;
	}
}