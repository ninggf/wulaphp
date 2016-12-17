<?php
namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\DialectException;

/**
 * 查询基类
 *
 * @package wulaphp\db\sql
 * @method toArray()
 * @method get($index = 0, $field = null)
 * @method first()
 * @method exist($filed = null)
 * @method field($field, $alias = null)
 * @method tree(&$options, $keyfield = 'id', $upfield = 'upid', $varfield = 'name', $stop = null, $from = 0, $level = 0)
 * @method total($field)
 */
abstract class QueryBuilder {
	const LEFT  = 'LEFT';
	const RIGHT = 'RIGHT';
	const INNER = '';

	private static $sqlCount    = 0;
	protected      $sql         = null;
	protected      $alias;
	protected      $options     = array();
	protected      $from        = array();
	protected      $joins       = array();
	protected      $where       = null;
	protected      $having      = array();
	protected      $limit       = null;
	protected      $group       = array();
	protected      $order       = array();
	protected      $error       = false;
	protected      $errorSQL    = '';
	protected      $errorValues = null;
	protected      $dumpSQL     = null;
	protected      $exception   = null;
	protected      $performed   = false;
	protected      $whereData   = [];
	/**
	 * @var \PDOStatement
	 */
	protected $statement = null;
	/**
	 * @var DatabaseDialect
	 */
	protected $dialect;
	/**
	 * @var BindValues
	 */
	protected $values = null;

	public function __destruct() {
		$this->close();
	}

	public function close() {
		$this->alias   = null;
		$this->dialect = null;
		$this->values  = null;
		$this->options = null;
		$this->from    = null;
		$this->joins   = null;
		$this->where   = null;
		$this->having  = null;
		$this->limit   = null;
		$this->group   = null;
		$this->order   = null;
	}

	/**
	 * @param DatabaseDialect $dialect
	 *
	 * @return QueryBuilder
	 */
	public function setDialect(DatabaseDialect $dialect) {
		$this->dialect = $dialect;

		return $this;
	}

	/**
	 * @param string $table
	 * @param string $alias
	 *
	 * @return QueryBuilder
	 */
	public function from($table, $alias = null) {
		$this->from [] = self::parseAs($table, $alias);

		return $this;
	}

	/**
	 * @param string $table
	 * @param string $on
	 * @param string $type
	 *
	 * @return QueryBuilder
	 */
	public function join($table, $on, $type = QueryBuilder::LEFT) {
		$table          = self::parseAs($table);
		$join           = array($table [0], $on, $type . ' JOIN ', $table [1]);
		$this->joins [] = $join;

		return $this;
	}

	/**
	 * left join.
	 *
	 * @param string $table
	 * @param array  ...$on
	 *
	 * @return QueryBuilder
	 */
	public function left($table, ...$on) {
		$this->join($table, Condition::cleanField($on[0]) . '=' . Condition::cleanField($on[1]), self::LEFT);

		return $this;
	}

	/**
	 * right join.
	 *
	 * @param string $table
	 * @param array  ...$on
	 *
	 * @return  QueryBuilder
	 */
	public function right($table, ...$on) {
		$this->join($table, Condition::cleanField($on[0]) . '=' . Condition::cleanField($on[1]), self::RIGHT);

		return $this;
	}

	/**
	 * inner join.
	 *
	 * @param string $table
	 * @param array  ...$on
	 *
	 * @return QueryBuilder
	 */
	public function inner($table, ...$on) {
		$this->join($table, Condition::cleanField($on[0]) . '=' . Condition::cleanField($on[1]), self::INNER);

		return $this;
	}

	/**
	 * 条件.
	 *
	 * @param array|Condition $con
	 * @param bool            $append
	 *
	 * @return $this
	 */
	public function where($con, $append = true) {
		if (is_array($con) && !empty ($con)) {
			$con = new Condition ($con, $this->alias);
		}
		if ($con) {
			if ($append && $this->where) {
				$this->where [] = $con;
			} else {
				$this->performed = false;
				$this->sql       = null;
				$this->where     = $con;
			}
		}

		return $this;
	}

	/**
	 * 更新条件中的数据.
	 *
	 * @param $data
	 *
	 * @return QueryBuilder
	 */
	public function updateWhereData($data) {
		$this->performed = false;
		$this->whereData = array_merge($this->whereData, $data);

		return $this;
	}

	/**
	 * get the where condition.
	 *
	 * @return Condition
	 */
	public function getCondition() {
		return $this->where;
	}

	/**
	 * alias of getCondition.
	 *
	 * @return \wulaphp\db\sql\Condition
	 */
	public function getWhere() {
		return $this->getCondition();
	}

	/**
	 * @param $having
	 *
	 * @return QueryBuilder
	 */
	public function having($having) {
		if (!empty ($having)) {
			$this->having [] = $having;
		}

		return $this;
	}

	/**
	 * @param $fields
	 *
	 * @return QueryBuilder
	 */
	public function groupBy($fields) {
		if (!empty ($fields)) {
			$this->group [] = $fields;
		}

		return $this;
	}

	/**
	 * @param $field
	 *
	 * @return QueryBuilder
	 */
	public function asc($field) {
		$this->order [] = array($field, 'ASC');

		return $this;
	}

	/**
	 * @param $field
	 *
	 * @return QueryBuilder
	 */
	public function desc($field) {
		$this->order [] = array($field, 'DESC');

		return $this;
	}

	/**
	 * MySQL的随机排序.
	 *
	 * @param string $rand
	 *
	 * @return QueryBuilder
	 */
	public function rand($rand = 'RAND') {
		$this->order [] = array(imv($rand), '()');

		return $this;
	}

	/**
	 * 排序,多个排序字段用','分隔.
	 *
	 * 当<code>$field</code>为null时，尝试从请求中读取sort[name]做为$field，sort[dir] 做为$order.
	 *
	 * @param string $field 排序字段，多个字段使用,分隔.
	 * @param string $order a or d
	 *
	 * @return QueryBuilder
	 */
	public function sort($field = null, $order = 'a') {
		if ($field === null) {
			$field = rqst('sort.name');
			$order = rqst('sort.dir', 'a');
		}
		$orders = explode(',', strtolower($order));
		$fields = explode(',', $field);
		foreach ($fields as $i => $field) {
			$this->order [] = array($field, isset($orders[ $i ]) ? $orders[ $i ] : $orders[0]);
		}

		return $this;
	}

	/**
	 * limit.
	 *
	 * @param int      $start start position or limit.
	 * @param int|null $limit
	 *
	 * @return $this
	 */
	public function limit($start, $limit = null) {
		if ($limit === null) {
			$limit = intval($start);
			$start = 0;
		} else {
			$start = intval($start);
			$limit = intval($limit);
		}
		if ($start < 0) {
			$start = 0;
		}
		if (!$limit) {
			$limit = 1;
		}
		$this->limit = array($start, $limit);
		if ($this->statement) {
			$this->updateWhereData([':limit_0' => $start, ':limit_1' => $limit]);
		}

		return $this;
	}

	/**
	 * 分页.
	 * 如果$pageNo等于null，那么直接读取page[page]做为$pageNo和page[size]做为$size.
	 *
	 * @see QueryBuilder::limit()
	 *
	 * @param int|null $pageNo 页数,从1开始.
	 * @param int      $size   默认每页20条
	 *
	 * @return $this
	 */
	public function page($pageNo = null, $size = 20) {
		if ($pageNo === null) {
			$pageNo = irqst('page.page', 1);
			$size   = irqst('page.size', 20);
		}
		$pageNo = intval($pageNo);
		if ($pageNo <= 0) {
			$pageNo = 1;
		}

		return $this->limit(($pageNo - 1) * $size, $size);
	}

	/**
	 * @param $alias
	 *
	 * @return QueryBuilder
	 */
	public function alias($alias) {
		$this->alias = $alias;

		return $this;
	}

	/**
	 *
	 * @return string the alias of the table this query used.
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * get the dialect binding with this query.
	 *
	 * @return DatabaseDialect
	 */
	public function getDialect() {
		$this->checkDialect();

		return $this->dialect;
	}

	protected function checkDialect() {
		if (!$this->dialect instanceof DatabaseDialect) {
			throw new DialectException('Cannot connect to database server');
		}
	}

	/**
	 * @return \wulaphp\db\sql\BindValues
	 */
	public function getBindValues() {
		return $this->values;
	}

	/**
	 * @param BindValues $values
	 */
	public function setBindValues($values) {
		$this->values = $values;
	}

	/**
	 * 设置PDO option,只影响PDOStatement。
	 *
	 * @param array $options
	 */
	public function setPDOOptions($options) {
		$this->options = $options;
	}

	public function lastError() {
		return $this->error;
	}

	public function error() {
		return $this->error;
	}

	public function lastSQL() {
		return $this->errorSQL;
	}

	public function lastValues() {
		return $this->errorValues;
	}

	/**
	 * @param \PDOStatement|null $statement
	 *
	 * @return mixed
	 */
	public function dumpSQL(\PDOStatement $statement = null) {
		if ($statement) {
			@ob_start(PHP_OUTPUT_HANDLER_CLEANABLE);
			$statement->debugDumpParams();
			$this->dumpSQL = @ob_get_clean();

			return null;
		} else {
			return $this->dumpSQL;
		}
	}

	/**
	 * 上次执行是否成功.
	 *
	 * @return bool
	 */
	public function success() {
		return empty ($this->error) ? true : false;
	}

	/**
	 * alias of exec.
	 *
	 * @param bool $checkNum
	 *
	 * @return bool
	 */
	public function perform($checkNum = false) {
		return $this->exec($checkNum);
	}

	/**
	 * 执行update,insert,delete语句.
	 *
	 * @param boolean $checkNum false 不检测,null直接返回影响的数量
	 *                          是否检测影响的条数.
	 *
	 * @return boolean
	 * @throws \PDOException
	 */
	public function exec($checkNum = false) {
		$cnt = $this->count();
		if ($cnt === false) {
			if ($this->exception instanceof \PDOException) {
				throw $this->exception;
			}

			return false;
		} else if ($this instanceof InsertSQL) {
			if ($checkNum || is_null($checkNum)) {
				return $cnt > 0;
			} else {
				$ids = $this->lastInsertIds();

				return $ids;
			}
		} else if (is_null($checkNum)) {
			return $cnt;
		} else if ($checkNum) {
			return $cnt > 0;
		} else {
			return true;
		}
	}

	public static function addSqlCount() {
		self::$sqlCount++;
	}

	public static function getSqlCount() {
		return self::$sqlCount;
	}

	protected function sanitize($var) {
		$this->checkDialect();
		if (is_string($var)) {
			return $this->dialect->sanitize($var);
		} else if (is_array($var)) {
			array_walk_recursive($var, array($this, 'sanitizeAry'));

			return $var;
		} else {
			return $var;
		}
	}

	/**
	 * work through an array to sanitize it, do not call this function directly.
	 * it is used internally.
	 *
	 * @see        sanitize()
	 *
	 * @param mixed $item
	 *
	 * @deprecated .
	 */
	public function sanitizeAry(&$item) {
		if (is_string($item)) {
			$item = $this->dialect->sanitize($item);
		}
	}

	protected static function parseAs($str, $alias1 = null) {
		$table = preg_split('#\b(as|\s+)\b#i', trim($str));
		if (count($table) == 1) {
			$name  = $table [0];
			$alias = $alias1;
		} else {
			$name  = $table [0];
			$alias = trim(array_pop($table));
		}

		return array(trim($name), $alias);
	}

	protected function prepareFrom($froms) {
		$_froms = array();
		if ($froms) {
			foreach ($froms as $from) {
				$table     = $this->dialect->getTableName($from [0]);
				$alias     = empty ($from [1]) ? $table : $from [1];
				$_froms [] = array($table, $alias);
			}
		}

		return $_froms;
	}

	protected function prepareJoins($joins) {
		$_joins = array();
		if ($joins) {
			foreach ($joins as $join) {
				$table     = $this->dialect->getTableName($join [0]);
				$alias     = empty ($join [3]) ? $table : $join [3];
				$_joins [] = array($table, $join [1], $join [2], $alias);
			}
		}

		return $_joins;
	}

	/**
	 * prepare the fields in select SQL
	 *
	 * @param array      $fields
	 * @param BindValues $values
	 *
	 * @return string
	 */
	protected function prepareFields($fields, $values) {
		$_fields = array();
		foreach ($fields as $field) {
			if ($field instanceof Query) { // sub-select SQL as field
				$field->setDialect($this->dialect);
				$field->setBindValues($values);
				$as = $field->getAlias();
				if ($as) {
					$_fields [] = '(' . $field . ') AS ' . $this->sanitize('`' . $as . '`');
				}
			} elseif ($field instanceof ImmutableValue) {
				$_fields [] = $field->__toString();
			} else { // this is simple field
				$_fields [] = $this->sanitize($field);
			}
		}
		if ($_fields) {
			return implode(',', $_fields);
		} else {
			return false;
		}
	}

	/**
	 * 执行方法.
	 * @return mixed
	 */
	public abstract function count();

	/**
	 *
	 * @return string
	 */
	public abstract function getSqlString();
}