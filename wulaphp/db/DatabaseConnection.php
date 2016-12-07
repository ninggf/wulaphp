<?php
namespace wulaphp\db;

use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\sql\DeleteSQL;
use wulaphp\db\sql\InsertSQL;
use wulaphp\db\sql\Query;
use wulaphp\db\sql\UpdateSQL;

/**
 * 数据库连接.
 *
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 */
class DatabaseConnection {
	/**
	 * @var \wulaphp\db\dialect\DatabaseDialect
	 */
	private $dialect = null;

	public $error = null;

	private $inTrans = false;

	public function __construct($dialect) {
		if (!$dialect instanceof DatabaseDialect) {
			throw new \Exception('the dialect is not instance of DatabaseDialect');
		}
		$this->dialect = $dialect;
	}

	/**
	 *
	 * @return \wulaphp\db\dialect\DatabaseDialect
	 */
	public function getDialect() {
		return $this->dialect;
	}

	/**
	 * start a database transaction
	 *
	 * @return boolean
	 */
	public function start() {
		if ($this->inTrans) {
			return true;
		}
		$dialect = $this->dialect;
		try {
			$rst = $dialect->beginTransaction();
			if ($rst) {
				$this->inTrans = true;

				return true;
			}
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
		}

		return false;
	}

	/**
	 * commit a transaction
	 * @return bool
	 */
	public function commit() {
		if (!$this->inTrans) {
			return false;
		}
		$dialect       = $this->dialect;
		$this->inTrans = false;
		try {
			return $dialect->commit();
		} catch (\PDOException $e) {
			$this->error = $e->getMessage();
		}

		return false;
	}

	/**
	 * rollback a transaction
	 * @return bool
	 */
	public function rollback() {
		if (!$this->inTrans) {
			return false;
		}
		$this->inTrans = false;
		$dialect       = $this->dialect;
		try {
			return $dialect->rollBack();
		} catch (\PDOException $e) {
			$this->error = $e->getMessage();
		}

		return false;
	}

	/**
	 * 在事务中运行.
	 *
	 * @param \Closure $trans 事务过程函数, 它有一个参数为当前数据库连接实例.
	 *                        事务过程函数返回非真值[null,false,'',0,空数组等]或抛出任何异常都将导致事务回滚.
	 *
	 * @return mixed
	 */
	public function trans(\Closure $trans) {
		$rst = $this->start();
		if ($rst) {
			try {
				$rst = $trans($this);
				if (empty($rst)) {
					$this->rollback();

					return $rst;
				} elseif ($this->commit()) {
					return $rst;
				}
			} catch (\Exception $e) {
				$this->error = $e->getMessage();
				$this->rollback();
			}
		}

		return null;
	}

	/**
	 * 锁定表.
	 *
	 * @param string          $table
	 * @param DatabaseDialect $dialect
	 */
	public function lock($table, $dialect = null) {
		if ($dialect == null) {
			$dialect = $this->dialect;
		}
		$table = $dialect->getTableName($table);
		$dialect->query("LOCK TABLES `" . $table . "` ");
	}

	/**
	 * @param DatabaseDialect $dialect
	 */
	public function unlock($dialect = null) {
		if ($dialect == null) {
			$dialect = $this->dialect;
		}
		$dialect->query("UNLOCK TABLES");
	}

	/**
	 * execute a ddl SQL.
	 *
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public function exec($sql) {
		$dialect = $this->dialect;
		if (is_null($dialect)) {
			return false;
		}
		try {
			$sql = str_replace('{encoding}', $this->dialect->getCharset(), $sql);
			$dialect->exec($sql);
		} catch (\Exception $e) {
			$this->error = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * 最后插入的主键值.
	 *
	 * @param string $name
	 *
	 * @return null|string
	 */
	public function lastInsertId($name = null) {
		$dialect = $this->dialect;
		if (is_null($dialect)) {
			return null;
		}

		return $this->dialect->lastInsertId($name);
	}

	/**
	 *
	 * 执行delete,update, insert SQL.
	 *
	 * @param string $sql
	 * @param array  $args
	 *
	 * @return int|null
	 */
	public function cud($sql, ...$args) {
		$dialect = $this->dialect;
		if (is_null($dialect)) {
			return null;
		}
		try {
			$params    = [];
			$sql       = preg_replace_callback('#%(s|d)#', function ($r) use (&$params) {
				$params[] = $r[0];

				return '?';
			}, $sql);
			$statement = $dialect->prepare($sql);
			if ($params) {
				foreach ($params as $i => $v) {
					$statement->bindValue($i + 1, $args[ $i ], $v == '%d' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
				}
			}
			$rst = $statement->execute();

			if ($rst) {
				$result = $statement->rowCount();

				return $result;
			}
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
		}

		return null;
	}

	/**
	 *
	 * 执行SQL查询,select a from a where a=%s and %d.
	 *
	 * @param string $sql
	 * @param array  $args
	 *
	 * @return array|null
	 */
	public function query($sql, ...$args) {
		$dialect = $this->dialect;
		if (is_null($dialect)) {
			return null;
		}
		try {
			$options [ \PDO::ATTR_CURSOR ] = \PDO::CURSOR_SCROLL;
			$params                        = [];
			$sql                           = preg_replace_callback('#%(s|d)#', function ($r) use (&$params) {
				$params[] = $r[0];

				return '?';
			}, $sql);
			$statement                     = $dialect->prepare($sql, $options);
			if ($params) {
				foreach ($params as $i => $v) {
					$statement->bindValue($i + 1, $args[ $i ], $v == '%d' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
				}
			}
			$rst = $statement->execute();

			if ($rst) {
				$result = $statement->fetchAll(\PDO::FETCH_ASSOC);

				return $result;
			}
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
		}

		return null;
	}

	/**
	 * 查询.
	 *
	 * @param array ...$fields
	 *
	 * @return \wulaphp\db\sql\Query
	 */
	public function select(...$fields) {
		$sql = new Query($fields);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 更新.
	 *
	 * @param string $table
	 *
	 * @return \wulaphp\db\sql\UpdateSQL
	 */
	public function update($table) {
		$sql = new UpdateSQL($table);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 删除.
	 * @return \wulaphp\db\sql\DeleteSQL
	 */
	public function delete() {
		$sql = new DeleteSQL();
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 插入或批量.
	 *
	 * @param array $data
	 * @param bool  $batch
	 *
	 * @return \wulaphp\db\sql\InsertSQL
	 */
	public function insert($data, $batch = false) {
		$sql = new InsertSQL($data, $batch);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 取表名.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getTableName($name) {
		if ($this->dialect) {
			return $this->dialect->getTableName($name);
		}

		return $name;
	}
}