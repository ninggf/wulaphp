<?php

namespace wulaphp\db\sql;

use wulaphp\db\DialectException;
use wulaphp\db\Orm;
use wulaphp\db\TableLocker;

/**
 * Class Query
 * @package wulaphp\db\sql
 * @property Orm $orm
 */
class Query extends QueryBuilder implements \Countable, \ArrayAccess, \Iterator {
	private $fields         = [];
	private $countperformed = false;
	private $size           = 0;
	private $count          = 0;
	private $resultSet      = [];//当前结果
	private $resultSets     = [];//所有结果
	private $resultIdx      = 0;//当前结果指针，用于遍历
	private $maxIdx         = 0;//最大指针
	private $treeKey        = null;
	private $treePad        = true;
	private $orm            = null;
	private $eagerFields    = [];
	private $forupdate      = false;

	/**
	 * Query constructor.
	 */
	public function __construct() {
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
		$this->fields     = null;
		$this->resultSet  = null;
		$this->resultSets = null;
		$this->treeKey    = null;
		$this->close();
	}

	/**
	 * 关闭statement
	 */
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

	/**
	 * 设置tree option的主键.
	 *
	 * @param string $key 主键字段.
	 *
	 * @return \wulaphp\db\sql\Query
	 */
	public function treeKey($key) {
		$this->treeKey = $key;

		return $this;
	}

	/**
	 * 是否添加&nbsp;
	 *
	 * @param bool $pad
	 *
	 * @return Query
	 */
	public function treepad($pad = true) {
		$this->treePad = $pad;

		return $this;
	}

	/**
	 * 生成树型SELECT Options.
	 *
	 * @param array   $options 结果数组引用.
	 * @param string  $keyfield
	 * @param string  $upfield
	 * @param string  $varfield
	 * @param string  $stop
	 * @param integer $from
	 * @param integer $level
	 */
	public function tree(&$options, $keyfield = 'id', $upfield = 'upid', $varfield = 'name', $stop = null, $from = 0, $level = 0) {
		if ($level == 0) {
			$con = new Condition ([$upfield => $from]);
			$this->where($con);
		} else {
			//更新查询条件，重新查询
			$this->updateWhereData([$upfield => $from]);
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
					$this->tree($options, $keyfield, $upfield, $varfield, $stop, $key, $level + 1);
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

		return $this->total($filed) > 0;
	}

	/**
	 * 符合条件的记录总数.
	 *
	 * Specify the $field argument to perform a 'select count($field)'
	 * operation, if the SQL has a having sub-sql, please note that the $field
	 * variables must contain the fields.
	 *
	 * @param string $field
	 *
	 * @return int
	 */
	public function total($field = '*') {
		if (!$this->countperformed || $this->whereData) {
			$this->performCount($field);
		}

		return $this->count;
	}

	/**
	 * 1.
	 * The implementation of Countable interface, so, you can count this class
	 * instance directly to get the size of the result set.<br/>
	 *
	 * @param string $id
	 *
	 * @return integer the number of result set.
	 */
	public function count($id = '*') {
		if (!$this->countperformed) {
			$this->performCount($id);
		}

		return $this->count;
	}

	public function offsetExists($offset) {
		if (!$this->performed) {
			$this->select();
		}

		return isset ($this->resultSet [ $offset ]);
	}

	public function offsetGet($offset) {
		if (key_exists($offset, $this->resultSet)) {
			return $this->resultSet [ $offset ];
		}

		if (!$this->performed) {
			$this->select();
		}

		if (!$this->resultSet) {
			return null;
		} else if (is_numeric($offset) && isset($this->resultSets[ $offset ])) {
			return $this->resultSets[ $offset ];
		} else if (key_exists($offset, $this->resultSet)) {
			return $this->resultSet [ $offset ];
		} else if ($this->orm) {
			return $this->orm->getData($this->resultIdx, $offset, $this->resultSets, isset($this->eagerFields[ $offset ]));
		}

		return null;
	}

	public function offsetSet($offset, $value) {
		if ($offset == 'orm') {
			$this->orm = $value;
		}
	}

	public function offsetUnset($offset) {
	}

	/**
	 * 查询并锁定.
	 *
	 * @return array|bool 成功返回查询到的数据,失败返回false.
	 */
	public function forupdate() {
		try {
			$this->checkDialect();
		} catch (DialectException $e) {
			return false;
		}
		// 不在事务中锁定失败.
		if (!$this->dialect->inTransaction()) {
			return false;
		}
		$this->forupdate = true;
		$data            = $this->get();
		// 成功
		if ($data) {
			return $data;
		}

		return false;
	}

	/**
	 * 基于此查询获取一个锁.
	 *
	 * @return \wulaphp\db\TableLocker
	 */
	public function locker() {
		return new TableLocker($this);
	}

	/**
	 * 取一行或一行中的一个字段的值.
	 *
	 * @param integer|string|array $index 结果集中的行号或字段名或条件.
	 * @param string               $field 结果集中的字段名.
	 *
	 * @return Query|array|null|mixed
	 *  $index是数据时返回Query实例;$index是字符时返回null或字符;$index是数字，$field为null时返回array，$field不为null时返回null或字符.
	 */
	public function get($index = 0, $field = null) {
		if (is_array($index)) {
			return $this->where($index);
		}

		if (is_string($index)) {
			$field = $index;
			$this->field($field);
			$index = 0;
			$this->limit(0, 1);
		}

		if (!$this->performed) {
			$this->select();
		}

		if (isset ($this->resultSets [ $index ])) {
			$row = $this->resultSets [ $index ];
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
	 * 读取第一条记录.
	 *
	 * @param array $default 默认值.
	 *
	 * @return array|mixed
	 */
	public function first($default = []) {
		if (!$this->performed) {
			$this->select();
		}
		if (isset ($this->resultSets [0])) {
			return $this->resultSets[0];
		}

		return $default;
	}

	/**
	 * 将行数据转换为字符.
	 *
	 * @param string $field
	 * @param string $sep
	 * @param null   $cb
	 *
	 * @return string
	 */
	public function implode($field, $sep = ',', $cb = null) {
		$rss = [];
		if ($cb instanceof \Closure) {
			foreach ($this->toArray() as $r) {
				$rss[] = $cb($r[ $field ]);
			}
		} else {
			foreach ($this->toArray() as $r) {
				$rss[] = $r[ $field ];
			}
		}

		return implode($sep, $rss);
	}

	/**
	 * 将结果集变为array或map
	 *
	 * @param string   $var  值字段.
	 * @param string   $key  键字段.
	 * @param array    $rows 初始数据
	 * @param \Closure $cb   过滤函数（仅当指定$val或$key时有用）
	 *
	 * @return array
	 */
	public function toArray($var = null, $key = null, $rows = [], $cb = null) {
		if (!$this->performed) {
			$this->select();
		}
		$rows = is_array($rows) ? $rows : [];
		if (is_array($var)) {
			$rows = $var;
			$var  = null;
		}
		if ($var == null && $key == null) {
			if ($rows) {
				foreach ($rows as $row) {
					if (is_array($row)) {
						array_unshift($this->resultSets, $row);
					}
				}
			}

			return $this->resultSets;
		} else if ($var != null) {
			if ($cb) {
				foreach ($this->resultSets as $row) {
					$value = $row [ $var ];
					if ($key != null && isset ($row [ $key ])) {
						$id           = $row [ $key ];
						$rows [ $id ] = $cb($value);
					} else {
						$rows [] = $cb($value);
					}
				}
			} else {
				foreach ($this->resultSets as $row) {
					$value = $row [ $var ];
					if ($key != null && isset ($row [ $key ])) {
						$id           = $row [ $key ];
						$rows [ $id ] = $value;
					} else {
						$rows [] = $value;
					}
				}
			}

			return $rows;
		} else if ($key != null) {
			if ($cb) {
				foreach ($this->resultSets as $row) {
					$id           = $row [ $key ];
					$rows [ $id ] = $row;
				}
			} else {
				foreach ($this->resultSets as $row) {
					$id           = $row [ $key ];
					$rows [ $id ] = $cb($row);
				}
			}

			return $rows;
		}

		return [];
	}

	/**
	 * 将结果转换为普通数组.
	 *
	 * @return array
	 */
	public function ary() {
		if (!$this->performed) {
			$this->select();
		}

		return $this->resultSet;
	}

	/**
	 * 从crumbs[0]开始逐级向上查询.
	 *
	 * @param array  $crumbs [0=>[$idkey=>idvalue,$upkey=>keyvalue]]
	 * @param string $idkey
	 * @param string $upkey
	 */
	public function recurse(&$crumbs, $idkey = 'id', $upkey = 'upid') {
		if (!$this->where) {
			$con = new Condition ([$idkey => $crumbs [0] [ $upkey ]]);
			$this->where($con, false);
		} else {
			$this->updateWhereData([$idkey => $crumbs [0] [ $upkey ]]);
		}
		$rst = $this->get();
		if ($rst) {
			array_unshift($crumbs, $rst);
			if (!empty ($rst [ $upkey ])) {
				$this->recurse($crumbs, $idkey, $upkey);
			}
		}
	}

	/**
	 * eager loading fields
	 *
	 * @param array ...$fields
	 *
	 * @return \wulaphp\db\sql\Query
	 */
	public function with(...$fields) {
		foreach ($fields as $f) {
			$this->eagerFields[ $f ] = $f;
		}

		return $this;
	}

	public function __toString() {
		if (!$this->sql) {
			$sql = $this->getSQL();
		} else {
			$sql = $this->sql;
		}

		return $sql;
	}

	/**
	 * @return null|string
	 */
	public function getSqlString() {
		return $this->__toString();
	}

	/**
	 * ORM work here for hasOne relationship.
	 *
	 * @param string $field 字段
	 *
	 * @return mixed
	 */
	public function __get($field) {
		return $this->offsetGet($field);
	}

	/**
	 * ORM work here for hasMany and belongsToMany.
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public function __call($name, $args) {
		if (!$this->performed) {
			$this->select();
		}
		if (empty($this->resultSet)) {
			return $this->emptyQuery();
		}
		if ($this->orm) {
			$q = $this->orm->getQuery($this->resultIdx, $name, $this->resultSets);
			if (empty($q)) {
				$q = $this->emptyQuery();
			}

			return $q;
		}

		return $this->emptyQuery();
	}

	public function __set($field, $value) {
		$this->{$field} = $value;
	}

	/**
	 * @see \Iterator::current()
	 * @return \wulaphp\db\sql\Query|mixed
	 */
	public function current() {
		return $this;
	}

	/**
	 * @see \Iterator::next()
	 */
	public function next() {
		$this->resultIdx++;
		if ($this->resultIdx <= $this->maxIdx) {
			$this->resultSet = $this->resultSets[ $this->resultIdx ];
		}
	}

	/**
	 * @see \Iterator::key()
	 * @return int|mixed
	 */
	public function key() {
		return $this->resultIdx;
	}

	/**
	 * @see \Iterator::valid()
	 * @return bool
	 */
	public function valid() {
		return $this->resultIdx <= $this->maxIdx && $this->size > 0;
	}

	/**
	 * @see \Iterator::rewind()
	 */
	public function rewind() {
		if (!$this->performed) {
			$this->select();
		}
		$this->resultIdx = 0;
		if ($this->size > 0) {
			$this->resultSet = $this->resultSets[0];
		} else {
			$this->resultSet = [];
		}
	}

	/**
	 * perform the select statement.
	 */
	private function select() {
		$this->performed  = true;
		$this->resultSet  = [];
		$this->resultSets = [];
		$this->resultIdx  = 0;
		$this->maxIdx     = 0;
		$this->size       = 0;
		try {
			$this->checkDialect();
		} catch (DialectException $e) {
			return;
		}
		//生成SQL
		if (!$this->sql) {
			$this->sql = $this->getSQL();
			if (!$this->sql) {
				$this->error       = 'can not generate the SQL';
				$this->errorSQL    = '';
				$this->errorValues = $this->values->__toString();

				return;
			}
			//重新生成SQL.
			$this->statement = null;
		}
		//生成prepare statement
		if (!$this->statement) {
			$this->prepareStatement();
		}
		//执行
		if ($this->statement) {
			try {
				if ($this->values) {
					foreach ($this->values as $value) {
						list ($name, $val, $type, $field, $rkey) = $value;
						if ($this->whereData) {
							$val = isset($this->whereData[ $rkey ]) ? $this->whereData[ $rkey ] : (isset($this->whereData[ $name ]) ? $this->whereData[ $name ] : $val);
						}
						if (!$this->statement->bindValue($name, $val, $type)) {
							$this->errorSQL    = $this->sql;
							$this->errorValues = $this->values->__toString();
							$this->error       = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name . '(' . $field . ')';

							return;
						}
					}
				}
				$rst = $this->statement->execute();
				QueryBuilder::addSqlCount();
				if ($rst) {
					$this->resultSets = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
					$this->size       = count($this->resultSets);
					if ($this->size > 0) {
						$this->maxIdx    = $this->size - 1;
						$this->resultSet = $this->resultSets[0];
					}
				} else {
					$this->dumpSQL($this->statement);
				}
			} catch (\PDOException $e) {
				$this->exception   = $e;
				$this->error       = $e->getMessage();
				$this->errorSQL    = $this->sql;
				$this->errorValues = $this->values->__toString();
			}
		}
	}

	/**
	 * perform the select count($field) statement.
	 *
	 * @param string $field
	 */
	private function performCount($field = '*') {
		try {
			$this->checkDialect();
		} catch (DialectException $e) {
			$this->error          = $e->getMessage();
			$this->count          = 0;
			$this->errorSQL       = '';
			$this->errorValues    = null;
			$this->countperformed = true;

			return;
		}
		$this->count = false;
		$values      = new BindValues ();
		$fields [0]  = 'COUNT(' . $field . ')';
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
						list ($name, $val, $type, $field) = $value;
						if ($this->whereData) {
							$val = isset($this->whereData[ $field ]) ? $this->whereData[ $field ] : (isset($this->whereData[ $name ]) ? $this->whereData[ $name ] : $val);
						}
						if (!$statement->bindValue($name, $val, $type)) {
							$this->countperformed = true;
							$this->count          = 0;
							$this->errorSQL       = $sql;
							$this->errorValues    = $values->__toString();
							$this->error          = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;

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
				$this->count       = 0;
				$this->errorSQL    = $sql;
				$this->errorValues = $values->__toString();
			}
		} else {
			$this->count       = 0;
			$this->errorSQL    = '';
			$this->errorValues = $values->__toString();
			$this->error       = 'can not generate the SQL';
		}
		$this->countperformed = true;
	}

	/**
	 * get the raw SQL.
	 * @return string
	 */
	private function getSQL() {
		try {
			$this->checkDialect();
		} catch (DialectException $e) {
			return null;
		}
		if (!$this->values) {
			$this->values = new BindValues ();
		}
		$fields = $this->prepareFields($this->fields, $this->values);
		$from   = $this->prepareFrom($this->sanitize($this->from));
		$joins  = $this->prepareJoins($this->sanitize($this->joins));
		$having = $this->sanitize($this->having);
		$group  = $this->sanitize($this->group);
		$order  = $this->sanitize($this->order);

		return $this->dialect->getSelectSQL($fields, $from, $joins, $this->where, $having, $group, $order, $this->limit, $this->values, $this->forupdate);
	}

	/**
	 * 准备PDOStatement
	 */
	private function prepareStatement() {
		$this->options [ \PDO::ATTR_CURSOR ] = \PDO::CURSOR_SCROLL;
		try {
			$this->statement = $this->dialect->prepare($this->sql, $this->options);
		} catch (\PDOException $e) {
			$this->exception   = $e;
			$this->error       = $e->getMessage();
			$this->size        = false;
			$this->errorSQL    = $this->sql;
			$this->errorValues = $this->values->__toString();
			log_error($this->error . '[' . $this->sql . ']', 'sql.err');
		}
	}

	protected function emptyQuery() {
		$q                 = new Query();
		$q->resultSets     = [];
		$q->resultSet      = [];
		$q->performed      = true;
		$q->countperformed = true;

		return $q;
	}
}