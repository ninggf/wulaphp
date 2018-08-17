<?php

namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\DialectException;

/**
 * 查询基类
 */
abstract class QueryBuilder {
	const LEFT  = 'LEFT';
	const RIGHT = 'RIGHT';
	const INNER = '';

	private static $sqlCount    = 0;
	protected      $sql         = null;
	protected      $alias;
	protected      $options     = [];
	protected      $from        = [];
	protected      $joins       = [];
	protected      $where       = null;
	protected      $having      = [];
	protected      $limit       = null;
	protected      $group       = [];
	protected      $order       = [];
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

	/**
	 * 关闭
	 */
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
		if ($this->statement) {
			$this->statement->closeCursor();
			$this->statement = null;
		}
	}

	/**
	 * @param DatabaseDialect|null $dialect
	 *
	 * @return $this
	 */
	public function setDialect(?DatabaseDialect $dialect) {
		$this->dialect = $dialect;

		return $this;
	}

	/**
	 * @param string $table
	 * @param string $on
	 * @param string $type
	 *
	 * @return $this
	 */
	public function join($table, $on, $type = QueryBuilder::LEFT) {
		$table          = self::parseAs($table);
		$join           = [$table [0], $on, $type . ' JOIN ', $table [1]];
		$this->joins [] = $join;

		return $this;
	}

	/**
	 * left join.
	 *
	 * @param string $table
	 * @param array  ...$on
	 *
	 * @return $this
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
	 * @return $this
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
	 * @return $this
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
	 * @return $this
	 */
	public function updateWhereData($data) {
		$this->performed = false;
		$this->whereData = array_merge($this->whereData, $data);

		return $this;
	}

	/**
	 * get the where condition.
	 *
	 * @return \wulaphp\db\sql\Condition
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
		return $this->where;
	}

	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function asc($field) {
		$this->order [] = [$field, 'a'];

		return $this;
	}

	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function desc($field) {
		$this->order [] = [$field, 'd'];

		return $this;
	}

	/**
	 * MySQL的随机排序.
	 *
	 * @param string $rand
	 *
	 * @return $this
	 */
	public function rand($rand = 'RAND') {
		$this->order [] = [imv($rand), '()'];

		return $this;
	}

	/**
	 * 排序,多个排序字段用','分隔.
	 *
	 * 当<code>$field</code>为null时，尝试从请求中读取sort[name]做为$field，sort[dir] 做为$order.
	 *
	 * @param string|array $field 排序字段，多个字段使用,分隔.
	 * @param string       $order a or d
	 *
	 * @return $this
	 */
	public function sort($field = null, $order = 'a') {
		if ($field === null) {
			$field = rqst('sort.name');
			$order = rqst('sort.dir', 'a');
		}
		if ($field) {
			if (is_string($field)) {
				$orders = explode(',', strtolower($order));
				$fields = explode(',', $field);
				foreach ($fields as $i => $field) {
					$this->order [] = [$field, isset($orders[ $i ]) ? $orders[ $i ] : $orders[0]];
				}
			} else if (is_array($field)) {
				$this->sort($field[0], isset($field[1]) ? $field[1] : 'a');
			}
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
		$this->limit = [$start, $limit];
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
			$pageNo = irqst('pager.page', 1);
			$size   = irqst('pager.size', 20);
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
	 * @return $this
	 */
	public function alias($alias) {
		$this->alias = $alias;

		return $this;
	}

	/**
	 * 获取别名.
	 *
	 * @return string the alias of the table this query used.
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * get the dialect binding with this query.
	 *
	 * @return \wulaphp\db\dialect\DatabaseDialect
	 */
	public function getDialect() {
		try {
			$this->checkDialect();

			return $this->dialect;
		} catch (DialectException $e) {
			return null;
		}
	}

	/**
	 * 检测数据库连接是否有效.
	 *
	 * @throws \wulaphp\db\DialectException
	 */
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

	/**
	 * 最后错误信息
	 * @return string
	 */
	public function lastError() {
		return $this->error;
	}

	/**
	 * 最后错误信息
	 * @see \wulaphp\db\sql\QueryBuilder::lastError()
	 * @return string
	 */
	public function error() {
		return $this->error;
	}

	/**
	 * 最后出错的SQL.
	 *
	 * @return string
	 */
	public function lastSQL() {
		return $this->errorSQL;
	}

	/**
	 * 最后出错时的数据.
	 *
	 * @return array
	 */
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
	 * 添加执行SQL记数
	 * @deprecated
	 */
	public static function addSqlCount() {
		self::$sqlCount++;
	}

	/**
	 * 获取执行的SQL语句数量.
	 *
	 * @return int
	 * @deprecated
	 */
	public static function getSqlCount() {
		return self::$sqlCount;
	}

	/**
	 * work through an array to sanitize it, do not call this function directly.
	 * it is used internally.
	 *
	 * @see        sanitize()
	 *
	 * @param mixed $item
	 *
	 * @deprecated
	 */
	public function sanitizeAry(&$item) {
		if (is_string($item)) {
			$item = $this->dialect->sanitize($item);
		}
	}

	/**
	 * 清洗数据.
	 *
	 * @param array|string $var
	 *
	 * @return array|string
	 */
	protected function sanitize($var) {
		try {
			$this->checkDialect();
		} catch (DialectException $e) {
			return $var;
		}

		if (is_string($var)) {
			return $this->dialect->sanitize($var);
		} else if (is_array($var)) {
			array_walk_recursive($var, [$this, 'sanitizeAry']);

			return $var;
		} else {
			return $var;
		}
	}

	/**
	 * 解析AS语句.
	 *
	 * @param string      $str
	 * @param null|string $alias1
	 *
	 * @return array
	 */
	protected static function parseAs(string $str, ?string $alias1 = null) {
		$table = preg_split('#\b(as|\s+)\b#i', trim($str));
		if (count($table) == 1) {
			$name  = $table [0];
			$alias = $alias1;
		} else {
			$name  = $table [0];
			$alias = trim(array_pop($table));
		}

		return [trim($name), $alias];
	}

	/**
	 * 解析表.
	 *
	 * @param array $froms
	 *
	 * @return array
	 */
	protected function prepareFrom($froms) {
		$_froms = [];
		if ($froms) {
			foreach ($froms as $from) {
				$table     = $this->dialect->getTableName($from [0]);
				$alias     = empty ($from [1]) ? $table : $from [1];
				$_froms [] = [$table, $alias];
			}
		}

		return $_froms;
	}

	/**
	 * 解析连接查询.
	 *
	 * @param array $joins
	 *
	 * @return array
	 */
	protected function prepareJoins($joins) {
		$_joins = [];
		if ($joins) {
			foreach ($joins as $join) {
				$table     = $this->dialect->getTableName($join [0]);
				$alias     = empty ($join [3]) ? $table : $join [3];
				$_joins [] = [$table, $join [1], $join [2], $alias];
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
		$_fields = [];
		foreach ($fields as $field) {
			if ($field instanceof Query) { // sub-select SQL as field
				$field->setDialect($this->dialect);
				$field->setBindValues($values);
				$as = $field->getAlias();
				if ($as) {
					$_fields [] = '(' . $field . ') AS ' . $this->sanitize('`' . $as . '`');
				}
			} else if ($field instanceof ImmutableValue) {
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
	 * 最后执行的SQL语句.
	 * @return string
	 */
	public abstract function getSqlString();
}