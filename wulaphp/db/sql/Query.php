<?php

namespace wulaphp\db\sql;

class Query extends QueryBuilder implements \Countable, \ArrayAccess, \IteratorAggregate {
	private $fields         = array();
	private $performed      = false;
	private $countperformed = false;
	private $size           = 0;
	private $count          = 0;
	private $resultSet      = array();
	/**
	 * @var \PDOStatement
	 */
	private $statement;
	private $treeCon = null;
	private $treeKey = null;
	private $treePad = true;

	public function __construct() {
		parent::__construct();
		$args = func_get_args();
		if ($args) {
			foreach ($args as $a) {
				if (is_array($a)) {
					foreach ($a as $f) {
						$this->field($f);
					}
				} else {
					$this->field($a);
				}
			}
		} else {
			$this->field('*');
		}
	}

	public function __destruct() {
		$this->fields    = null;
		$this->resultSet = null;
		$this->treeCon   = null;
		$this->treeKey   = null;
		$this->close();
	}

	public function close() {
		parent::close();
		if ($this->statement) {
			$this->statement->closeCursor();
			$this->statement = null;
		}
	}

	/**
	 * append a field to result set.
	 *
	 * @param string|Query $field
	 * @param string       $alias
	 *
	 * @return Query
	 */
	public function field($field, $alias = null) {
		if (is_string($field)) {
			$fields = explode(',', $field);
			foreach ($fields as $field) {
				$this->fields [] = Condition::cleanField($field . ($alias ? ' AS ' . $alias : ''));
			}
		} else if ($field instanceof Query) {
			if ($alias) {
				$field->alias($alias);
			}
			$this->fields [] = $field;
		} else if ($field instanceof ImmutableValue) {
			if ($alias) {
				$field->alias($alias);
			}
			$this->fields [] = $field;
		}

		return $this;
	}

	public function treeWhere($con) {
		if (is_array($con) && !empty ($con)) {
			$con = new Condition ($con);
		}
		if ($con) {
			if ($this->treeCon) {
				$this->treeCon [] = $con;
			} else {
				$this->treeCon = $con;
			}
		}

		return $this;
	}

	public function treeKey($key) {
		$this->treeKey = $key;
		$this->fields  = array();
		$this->field($key);

		return $this;
	}

	/**
	 *
	 * @param bool $pad
	 *
	 * @return Query
	 */
	public function treePad($pad = true) {
		$this->treePad = $pad;

		return $this;
	}

	/**
	 * 生成树型SELECT Options.
	 *
	 * @param array   $options
	 *            结果数组引用.
	 * @param string  $keyfield
	 * @param string  $upfield
	 * @param string  $varfield
	 * @param string  $stop
	 * @param integer $from
	 * @param integer $level
	 */
	public function treeOption(&$options, $keyfield = 'id', $upfield = 'upid', $varfield = 'name', $stop = null, $from = 0, $level = 0) {
		$this->performed = false;
		$con             = new Condition (array($upfield => $from));
		if ($this->treeCon) {
			$con [] = $this->treeCon;
		}
		$this->where($con, false);
		if ($level == 0) {
			$this->field($keyfield);
			$this->field($upfield);
			$this->field($varfield);
		}

		$rows = $this->toArray();
		if ($rows) {

			if ($this->treePad) {
				$pad = str_pad('&nbsp;&nbsp;|--', ($level * 24 + 15), '&nbsp;', STR_PAD_LEFT);
			} else {
				$pad = '';
			}
			foreach ($rows as $data) {
				$tkey = $key = $data [ $keyfield ];
				if ($this->treeKey) {
					$tkey = $data [ $this->treeKey ];
				}
				$var = $data [ $varfield ];
				if ($stop == null || $key != $stop) {
					if ($this->treePad) {
						$options [ $tkey ] = $pad . ' ' . $var;
					} else {
						$options [ $tkey ] = $var;
					}
					$this->treeOption($options, $keyfield, $upfield, $varfield, $stop, $key, $level + 1);
				}
			}
		}
	}

	/**
	 * check if there is any row in database suits the condition.
	 *
	 * @param string $filed
	 *
	 * @return boolean
	 */
	public function exist($filed = null) {
		if ($filed) {
			$this->field($filed);
		}

		return $this->count($filed) > 0;
	}

	/**
	 * 1.
	 * The implementation of Countable interface, so, you can count this class
	 * instance directly to get the size of the result set.<br/>
	 * 2. Specify the $field argument to perform a 'select count($field)'
	 * operation, if the SQL has a having sub-sql, please note that the $field
	 * variables must contain the fields.
	 *
	 * @return integer the number of result set or the count total or false on error
	 *         SQL.
	 */
	public function count() {
		$field = func_get_args();
		if ($field == null) {
			if (!$this->performed) {
				$this->select();
			}

			return $this->size;
		} else {
			if (!$this->countperformed) {
				call_user_func_array(array($this, 'performCount'), func_get_args());
			}

			return $this->count;
		}

	}

	public function offsetExists($offset) {
		if (!$this->performed) {
			$this->select();
		}

		return isset ($this->resultSet [ $offset ]);
	}

	public function offsetGet($offset) {
		if (!$this->performed) {
			$this->select();
		}
		if (isset ($this->resultSet [ $offset ])) {
			return $this->resultSet [ $offset ];
		}

		return null;
	}

	public function offsetSet($offset, $value) {
	}

	public function offsetUnset($offset) {
	}

	public function getIterator() {
		if (!$this->performed) {
			$this->select();
		}

		return new \ArrayIterator ($this->resultSet);
	}

	/**
	 * 取一行或一行中的一个字段的值.
	 *
	 * @param integer|string $index
	 *            结果集中的行号或字段名.
	 * @param string         $field
	 *            结果集中的字段名.
	 *
	 * @return mixed
	 */
	public function get($index = 0, $field = null) {
		if (is_string($index)) {
			$field = $index;
			$this->field($field);
			$index = 0;
		}
		if (!$this->performed) {
			$this->select();
		}

		if (isset ($this->resultSet [ $index ])) {
			$row = $this->resultSet [ $index ];
			if ($field != null && isset ($row [ $field ])) {
				return $row [ $field ];
			}
			if ($field == null) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * 将结果集变为array或map
	 *
	 * @param string $var
	 *            值字段.
	 * @param string $key
	 *            键字段.
	 * @param array  $rows
	 *
	 * @return array
	 */
	public function toArray($var = null, $key = null, $rows = array()) {
		if (!$this->performed) {
			$this->select();
		}
		$rows = is_array($rows) ? $rows : array();
		if (is_array($var)) {
			$rows = $var;
			$var  = null;
		}
		if ($var == null && $key == null) {
			if ($rows) {
				foreach ($rows as $row) {
					if (is_array($row)) {
						array_unshift($this->resultSet, $row);
					}
				}
			}

			return $this->resultSet;
		} else if ($var != null) {
			foreach ($this->resultSet as $row) {
				$value = $row [ $var ];
				if ($key != null && isset ($row [ $key ])) {
					$id           = $row [ $key ];
					$rows [ $id ] = $value;
				} else {
					$rows [] = $value;
				}
			}

			return $rows;
		} else if ($key != null) {
			foreach ($this->resultSet as $row) {
				if (!isset ($row [ $key ])) {
					return $rows;
				}
				$id           = $row [ $key ];
				$rows [ $id ] = $row;
			}

			return $rows;
		}

		return array();
	}

	public function recurse(&$crumbs, $idkey = 'id', $upkey = 'upid') {
		$this->performed = false;
		$con             = new Condition (array($idkey => $crumbs [0] [ $upkey ]));
		$this->where($con, false);
		$rst = $this->get(0);
		if ($rst) {
			array_unshift($crumbs, $rst);
			if (!empty ($rst [ $upkey ])) {
				$this->recurse($crumbs, $idkey, $upkey);
			}
		}
	}

	public function __toString() {
		$sql = $this->getSQL();

		return $sql;
	}

	/**
	 * perform the select statement.
	 */
	private function select() {
		$this->checkDialect();
		$this->values    = null;
		$this->resultSet = array();
		$sql             = $this->getSQL();
		$this->sql       = $sql;
		if ($sql) {
			try {
				$this->options [ \PDO::ATTR_CURSOR ] = \PDO::CURSOR_SCROLL;
				$this->statement                     = $this->dialect->prepare($sql, $this->options);
				if ($this->values) {
					foreach ($this->values as $value) {
						list ($name, $val, $type) = $value;
						if (!$this->statement->bindValue($name, $val, $type)) {
							$this->performed   = true;
							$this->size        = false;
							$this->errorSQL    = $sql;
							$this->errorValues = $this->values->__toString();
							$this->error       = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;
							log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');

							return;
						}
					}
				}
				$rst = $this->statement->execute();
				QueryBuilder::addSqlCount();
				if ($rst) {
					$this->resultSet = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
					$this->statement->closeCursor();
					$this->size = count($this->resultSet);
				} else {
					$this->dumpSQL($this->statement);
				}
			} catch (\PDOException $e) {
				$this->exception   = $e;
				$this->error       = $e->getMessage();
				$this->size        = false;
				$this->errorSQL    = $sql;
				$this->errorValues = $this->values->__toString();
			}
		} else {
			$this->size        = false;
			$this->error       = 'can not generate the SQL';
			$this->errorSQL    = '';
			$this->errorValues = $this->values->__toString();
		}
		if ($this->error) {
			log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');
		}
		$this->performed = true;
	}

	/**
	 * perform the select count($field) statement.
	 *
	 */
	private function performCount() {
		$this->checkDialect();
		$this->count = false;
		$values      = new BindValues ();
		$fields      = func_get_args();
		$fields [0]  = 'COUNT(' . $fields [0] . ')';
		$fields      = $this->prepareFields($fields, $values);
		$from        = $this->prepareFrom($this->sanitize($this->from));
		$joins       = $this->prepareJoins($this->sanitize($this->joins));
		$having      = $this->sanitize($this->having);
		$group       = $this->sanitize($this->group);
		$sql         = $this->dialect->getCountSelectSQL($fields, $from, $joins, $this->where, $having, $group, $values);
		if ($sql) {
			try {
				$this->options [ \PDO::ATTR_CURSOR ] = \PDO::CURSOR_SCROLL;
				$statement                           = $this->dialect->prepare($sql, $this->options);
				if ($values) {
					foreach ($values as $value) {
						list ($name, $val, $type) = $value;
						if (!$statement->bindValue($name, $val, $type)) {
							$this->countperformed = true;
							$this->count          = false;
							$this->errorSQL       = $sql;
							$this->errorValues    = $values->__toString();
							$this->error          = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;
							log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');

							return;
						}
					}
				}
				$rst = $statement->execute();
				QueryBuilder::addSqlCount();
				if ($rst) {
					$resultSet = $statement->fetch(\PDO::FETCH_NUM);
					$statement->closeCursor();
					$this->count = intval($resultSet [0]);
				} else {
					$this->dumpSQL($statement);
				}
			} catch (\PDOException $e) {
				$this->error       = $e->getMessage();
				$this->count       = false;
				$this->errorSQL    = $sql;
				$this->errorValues = $values->__toString();
			}
		} else {
			$this->count       = false;
			$this->errorSQL    = '';
			$this->errorValues = $values->__toString();
			$this->error       = 'can not generate the SQL';
		}
		if ($this->error) {
			log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');
		}
		$this->countperformed = true;
	}

	public function getSqlString() {
		return $this->sql;
	}

	/**
	 * get the raw SQL.
	 * @return string
	 */
	private function getSQL() {
		$this->checkDialect();
		if (!$this->values) {
			$this->values = new BindValues ();
		}
		$fields = $this->prepareFields($this->fields, $this->values);
		$from   = $this->prepareFrom($this->sanitize($this->from));
		$joins  = $this->prepareJoins($this->sanitize($this->joins));
		$having = $this->sanitize($this->having);
		$group  = $this->sanitize($this->group);
		$order  = $this->sanitize($this->order);

		return $this->dialect->getSelectSQL($fields, $from, $joins, $this->where, $having, $group, $order, $this->limit, $this->values);
	}
}
