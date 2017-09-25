<?php

namespace wulaphp\db;

use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\sql\DeleteSQL;
use wulaphp\db\sql\InsertSQL;
use wulaphp\db\sql\Query;
use wulaphp\db\sql\UpdateSQL;
use wulaphp\wulaphp\db\ILock;

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
	 * 重新链接数据库.
	 *
	 * @throws \Exception
	 */
	public function reconnect() {
		$this->dialect = $this->dialect->reset();
		if (!$this->dialect instanceof DatabaseDialect) {
			throw new \Exception('cannot reconnect to the database.');
		}
	}

	/**
	 * start a database transaction
	 *
	 * @return boolean
	 */
	public function start() {
		if ($this->dialect) {
			$dialect = $this->dialect;
			if ($dialect->inTransaction()) {
				return true;
			}
			$rst = $dialect->beginTransaction();
			if ($rst) {
				return true;
			}
		}

		return false;
	}

	/**
	 * commit a transaction
	 * @return bool
	 */
	public function commit() {
		if ($this->dialect) {
			$dialect = $this->dialect;
			if ($dialect->inTransaction()) {
				try {
					return $dialect->commit();
				} catch (\PDOException $e) {
					$this->error = $e->getMessage();
				}
			}
		}

		return false;
	}

	/**
	 * rollback a transaction
	 * @return bool
	 */
	public function rollback() {
		if ($this->dialect) {
			$dialect = $this->dialect;
			if ($dialect->inTransaction()) {
				try {
					return $dialect->rollBack();
				} catch (\PDOException $e) {
					$this->error = $e->getMessage();
				}
			}
		}

		return false;
	}

	/**
	 * 在事务中运行.
	 * 事务过程函数返回非真值[null,false,'',0,空数组等]或抛出任何异常都将导致事务回滚.
	 *
	 * @param \Closure $trans 事务过程函数,声明如下:
	 *                        function trans(DatabaseConnection $con,mixed $data);
	 *                        1. $con 数据库链接
	 *                        2. $data 锁返回的数据.
	 * @param string   $error 错误信息
	 * @param ILock    $lock  锁.
	 *
	 * @return mixed|null  事务过程函数的返回值或null
	 */
	public function trans(\Closure $trans, &$error = null, ILock $lock = null) {
		try {
			$rst = $this->start();
			if ($rst) {
				try {
					$data = false;
					if ($lock && ($data = $lock->lock()) !== false) {
						throw new \Exception('Cannot get lock from ' . get_class($lock));
					}
					if ($lock) {
						$rst = call_user_func_array($trans, [$this, $data]);
					} else {
						$rst = call_user_func_array($trans, [$this]);
					}
					if (empty($rst)) {
						$this->rollback();

						return $rst;
					} else if ($this->commit()) {
						return $rst;
					} else {
						$error = $this->error = 'Cannot commit trans.';
					}
				} catch (\Exception $e) {
					$error = $this->error = $e->getMessage();
					$this->rollback();
				}
			}
		} catch (\Exception $e) {
			$error = $this->error = $e->getMessage();
		}

		return null;
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
			// 表前缀处理
			$sql = preg_replace_callback('#\{[a-z][a-z0-9_].*\}#i', function ($r) use ($dialect) {
				return $dialect->getTableName($r[0]);
			}, $sql);
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
			$params = 0;
			// 表前缀处理
			$sql = preg_replace_callback('#\{[a-z][a-z0-9_].*\}#i', function ($r) use ($dialect) {
				return $dialect->getTableName($r[0]);
			}, $sql);
			// 参数处理
			$sql = preg_replace_callback('#%(s|d|f)#', function ($r) use (&$params, $args, $dialect) {
				if ($r[0] == '%f') {
					$v = floatval($args[ $params ]);
				} else if ($r[0] == '%d') {
					$v = intval($args[ $params ]);
				} else {
					$v = $dialect->quote($args[ $params ], \PDO::PARAM_STR);
				}
				$params++;

				return $v;
			}, $sql);

			$rst = $dialect->exec($sql);

			return $rst === false ? null : $rst;

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
			$params = 0;
			// 表前缀处理
			$sql = preg_replace_callback('#\{[a-z][a-z0-9_].*\}#i', function ($r) use ($dialect) {
				return $dialect->getTableName($r[0]);
			}, $sql);
			// 参数处理
			$sql = preg_replace_callback('#%(s|d|f)#', function ($r) use (&$params, $args, $dialect) {
				if ($r[0] == '%f') {
					$v = floatval($args[ $params ]);
				} else if ($r[0] == '%d') {
					$v = intval($args[ $params ]);
				} else {
					$v = $dialect->quote($args[ $params ], \PDO::PARAM_STR);
				}
				$params++;

				return $v;
			}, $sql);

			$rst = $this->dialect->query($sql);

			if ($rst) {
				$result = $rst->fetchAll(\PDO::FETCH_ASSOC);
				$rst->closeCursor();

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
	 * @param array $table
	 *
	 * @return \wulaphp\db\sql\UpdateSQL
	 */
	public function update(...$table) {
		$sql = new UpdateSQL($table);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 删除.
	 *
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
	 * 批量插入.
	 *
	 * @param array $datas
	 *
	 * @return \wulaphp\db\sql\InsertSQL
	 */
	public function inserts($datas) {
		return $this->insert($datas, true);
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