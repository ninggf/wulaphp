<?php
namespace wulaphp\db\dialect;

use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;
use wulaphp\db\sql\ImmutableValue;
use wulaphp\db\sql\Query;

/**
 * MySQL Dialect.
 *
 * @author NingGuangfeng
 *
 */
class MySQLDialect extends DatabaseDialect {
	/**
	 * @param array      $fields
	 * @param array      $from
	 * @param array      $joins
	 * @param Condition  $where
	 * @param array      $having
	 * @param array      $group
	 * @param array      $order
	 * @param array      $limit
	 * @param BindValues $values
	 *
	 * @return string
	 */
	public function getSelectSQL($fields, $from, $joins, $where, $having, $group, $order, $limit, $values) {
		$sql = array('SELECT', $fields, 'FROM');
		$this->generateSQL($sql, $from, $joins, $where, $having, $group, $values);
		if ($order) {
			$_orders = array();
			foreach ($order as $o) {
				$_orders [] = $o [0] . ' ' . $o [1];
			}
			$sql [] = 'ORDER BY ' . implode(' , ', $_orders);
		}
		if ($limit) {
			$limit1 = $values->addValue('limit', $limit [0]);
			$limit2 = $values->addValue('limit', $limit [1]);
			$sql [] = 'LIMIT ' . $limit1 . ',' . $limit2;
		}
		$sql = implode(' ', $sql);

		return $sql;
	}

	/**
	 * @param                                $fields
	 * @param array                          $from
	 * @param array                          $joins
	 * @param Condition                      $where
	 * @param array                          $having
	 * @param array                          $group
	 * @param BindValues                     $values
	 *
	 * @return string
	 */
	public function getCountSelectSQL($fields, $from, $joins, $where, $having, $group, $values) {
		$sql = array('SELECT', $fields, 'FROM');
		$this->generateSQL($sql, $from, $joins, $where, $having, $group, $values);
		$sql = implode(' ', $sql);

		return $sql;
	}

	/**
	 * @param string     $into
	 * @param array      $data
	 * @param BindValues $values
	 *
	 * @return string
	 */
	public function getInsertSQL($into, $data, $values) {
		$sql    = "INSERT INTO $into (";
		$fields = $_values = array();
		foreach ($data as $field => $value) {
			$fields [] = Condition::cleanField($field);
			if ($value instanceof ImmutableValue) { // a immutable value
				$_values [] = $this->sanitize($value->__toString($this));
			} else if ($value instanceof Query) { // a sub-select SQL as a value
				$value->setBindValues($values);
				$value->setDialect($this);
				$_values [] = '(' . $value->__toString() . ')';
			} else {
				$_values [] = $values->addValue($field, $value);
			}
		}
		$sql .= implode(',', $fields) . ') VALUES (' . implode(' , ', $_values) . ')';

		return $sql;
	}

	/**
	 * @param array      $from
	 * @param array      $joins
	 * @param Condition  $where
	 * @param BindValues $values
	 * @param array      $order
	 * @param array      $limit
	 *
	 * @return string
	 */
	public function getDeleteSQL($from, $joins, $where, $values, $order, $limit) {
		$len = count($joins);
		if ($len == 0) {
			$sql [] = 'DELETE FROM ' . $from [0][0];
		} else {
			$sql [] = 'DELETE ' . $from[0][1] . ' FROM ' . implode(' AS ', $from [0]);
			foreach ($joins as $join) {
				$sql [] = $join [2] . ' ' . $join [0] . ' AS ' . $join [3] . ' ON (' . $join [1] . ')';
			}
		}

		if ($where && count($where) > 0) {
			$sql [] = 'WHERE';
			$sql [] = $where->getWhereCondition($this, $values);
		}
		if ($len == 0) {
			if ($order) {
				$_orders = array();
				foreach ($order as $o) {
					$_orders [] = $o [0] . ' ' . $o [1];
				}
				$sql [] = 'ORDER BY ' . implode(' , ', $_orders);
			}
			if ($limit) {
				$limit2 = $values->addValue('limit', $limit [1]);
				$sql [] = 'LIMIT ' . $limit2;
			}
		}

		return implode(' ', $sql);
	}

	/**
	 * @param array      $table
	 * @param array      $data
	 * @param Condition  $where
	 * @param BindValues $values
	 * @param array      $order
	 * @param array      $limit
	 *
	 * @return string
	 */
	public function getUpdateSQL($table, $data, $where, $values, $order, $limit) {
		$len = count($table);
		if ($len == 1) {
			$sql = array('UPDATE', implode(' AS ', $table[0]), 'SET');
		} else {
			$tables = [];
			foreach ($table as $t) {
				$tables[] = implode(' AS ', $t);
			}
			$sql = array('UPDATE', implode(' , ', $tables), 'SET');
		}

		$fields = array();
		foreach ($data as $field => $value) {
			$field = Condition::cleanField($field);
			if ($value instanceof Query) {
				$value->setBindValues($values);
				$value->setDialect($this);
				$fields [] = $this->sanitize($field) . ' =  (' . $value->__toString() . ')';
			} else if ($value instanceof ImmutableValue) {
				$fields [] = $this->sanitize($field) . ' =  ' . $this->sanitize($value->__toString($this));
			} else {
				$fields [] = $this->sanitize($field) . ' = ' . $values->addValue($field, $value);
			}
		}

		$sql [] = implode(' , ', $fields);
		if ($where && count($where) > 0) {
			$sql [] = 'WHERE';
			$sql [] = $where->getWhereCondition($this, $values);
		}

		if ($len == 1) {
			if ($order) {
				$_orders = array();
				foreach ($order as $o) {
					$_orders [] = $o [0] . ' ' . $o [1];
				}
				$sql [] = 'ORDER BY ' . implode(' , ', $_orders);
			}
			if ($limit) {
				$limit2 = $values->addValue('limit', $limit [1]);
				$sql [] = 'LIMIT ' . $limit2;
			}
		}

		return implode(' ', $sql);
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	protected function prepareConstructOption($options) {
		if ($options instanceof DatabaseConfiguration) {
			$options = $options->toArray();
		}
		$opts    = array_merge(array('encoding' => 'UTF8MB4', 'dbname' => '', 'host' => 'localhost', 'port' => 3306, 'user' => 'root', 'password' => 'root', 'driver_options' => array()), $options);
		$charset = isset ($opts ['encoding']) && !empty ($opts ['encoding']) ? $opts ['encoding'] : 'UTF8MB4';
		if ($charset == null) {
			$dsn = "mysql:dbname={$opts['dbname']};host={$opts['host']};port={$opts['port']}";
		} else {
			$dsn = "mysql:dbname={$opts['dbname']};host={$opts['host']};port={$opts['port']};charset={$charset}";
		}
		$this->charset = $charset;

		return array($dsn, $opts ['user'], $opts ['password'], $opts ['driver_options']);
	}

	/**
	 * @param string $database
	 * @param string $charset
	 *
	 * @return bool
	 */
	public function createDatabase($database, $charset = '') {
		$sql = "CREATE DATABASE IF NOT EXISTS `{$database}`";
		if ($charset) {
			$sql .= ' DEFAULT CHARACTER SET ' . $charset;
		} else if ($this->charset) {
			$sql .= ' DEFAULT CHARACTER SET ' . $this->charset;
		} else {
			$sql .= ' DEFAULT CHARACTER SET UTF8MB4';
		}
		try {
			$rst = $this->exec($sql);

			return $rst;
		} catch (\PDOException $e) {
			DatabaseDialect::$lastErrorMassge = $e->getMessage();

			return false;
		}
	}

	/**
	 * @return array
	 */
	public function listDatabases() {
		$dbs = array();
		$rst = $this->query('SHOW DATABASES');
		if ($rst) {
			$db = $rst->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($db as $d) {
				$dbs [] = $d ['Database'];
			}
		}

		return $dbs;
	}

	public function getDriverName() {
		return 'mysql';
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function sanitize($string) {
		return $string;
	}

	/**
	 * generate the common SQL for select and select count
	 *
	 * @param array      $sql
	 * @param array      $from
	 * @param array      $joins
	 * @param Condition  $where
	 * @param array      $having
	 * @param array      $group
	 * @param BindValues $values
	 */
	private function generateSQL(&$sql, $from, $joins, $where, $having, $group, $values) {
		$froms = array();
		foreach ($from as $f) {
			$froms [] = $f [0] . ' AS ' . $f [1];
		}
		$sql [] = implode(',', $froms);
		if ($joins) {
			foreach ($joins as $join) {
				$sql [] = $join [2] . ' ' . $join [0] . ' AS ' . $join [3] . ' ON (' . $join [1] . ')';
			}
		}
		if ($where && count($where) > 0) {
			$sql [] = 'WHERE ' . $where->getWhereCondition($this, $values);
		}
		if ($group) {
			$sql [] = 'GROUP BY ' . implode(' , ', $group);
		}
		if ($having) {
			$sql [] = 'HAVING ' . implode(' AND ', $having);
		}
	}

	public function getCharset() {
		return $this->charset;
	}
}