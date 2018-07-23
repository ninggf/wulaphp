<?php

namespace wulaphp\db;

use wulaphp\app\App;
use wulaphp\conf\DatabaseConfiguration;
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
	private        $dialect    = null;
	public         $error      = null;
	private        $name       = '';
	private        $transLevel = 0;
	private        $commitType = null;//提交类型
	private static $dbs        = [];

	/**
	 * DatabaseConnection constructor.
	 *
	 * @param $dialect
	 *
	 * @throws
	 */
	public function __construct(DatabaseDialect $dialect) {
		if (!$dialect instanceof DatabaseDialect) {
			throw new \Exception('the dialect is not instance of DatabaseDialect');
		}
		$this->dialect = $dialect;
	}

	/**
	 * 获取数据库连接实例.
	 *
	 * @param string|array|DatabaseConfiguration $name 数据库配置名/配置数组/配置实例.
	 *
	 * @return DatabaseConnection
	 * @throws \Exception
	 */
	public static function connect($name = null): DatabaseConnection {
		if ($name instanceof DatabaseConnection) {
			return $name;
		}
		if (defined('ARTISAN_TASK_PID')) {
			$pid = '@' . @posix_getpid();
		} else {
			$pid = '';
		}
		$config = $pname = false;
		if (is_array($name)) {
			$tmpname = implode('_', $name) . $pid;
			if (isset (self::$dbs [ $tmpname ])) {
				return self::$dbs [ $tmpname ];
			}
			$config = $name;
			$pname  = $tmpname;
		} else if (is_string($name)) {
			$pname = $name . $pid;
			if (isset (self::$dbs [ $pname ])) {
				return self::$dbs [ $pname ];
			}
			$config = App::cfgLoader()->loadDatabaseConfig($name);
		} else if ($name instanceof DatabaseConfiguration) {
			$config = $name;
			$pname  = $config->__toString() . $pid;
		}
		if ($config) {
			$dialect = DatabaseDialect::getDialect($config);
			if ($dialect) {
				$db                   = new DatabaseConnection($dialect);
				$db->name             = $pname;
				self::$dbs [ $pname ] = $db;

				return $db;
			}
		}

		return null;
	}

	/**
	 * 获取数据库驱动.
	 *
	 * @return \wulaphp\db\dialect\DatabaseDialect
	 */
	public function getDialect(): DatabaseDialect {
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
	 * 关闭连接.
	 */
	public function close() {
		if ($this->dialect) {
			$this->dialect->close();
		}
		if ($this->name && isset(self::$dbs[ $this->name ])) {
			unset(self::$dbs[ $this->name ]);
		}
	}

	/**
	 * start a database transaction
	 *
	 * @return boolean
	 */
	public function start(): bool {
		if ($this->dialect) {
			//允许事务嵌套
			$this->transLevel += 1;
			$dialect          = $this->dialect;
			if ($dialect->inTransaction()) {
				return true;
			}
			$rst = $dialect->beginTransaction();
			if ($rst) {
				return true;
			} else {
				$this->transLevel -= 1;
			}
		}

		return false;
	}

	/**
	 * commit a transaction
	 *
	 *
	 * @return bool
	 */
	public function commit(): bool {
		if ($this->dialect) {
			$dialect = $this->dialect;
			if ($dialect->inTransaction()) {
				$this->transLevel -= 1;
				if ($this->transLevel > 0) {
					return true;
				}
				try {
					if ($this->commitType == 'rollback') {
						$dialect->rollBack();

						//提交失败，因为在提交之前有人要回滚.
						return false;
					} else {
						return $dialect->commit();
					}
				} catch (\PDOException $e) {
					$this->error = $e->getMessage();
				} finally {
					$this->transLevel = 0;
					$this->commitType = null;
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
				if (!$this->commitType) {
					$this->commitType = 'rollback';
				}
				$this->transLevel -= 1;
				if ($this->transLevel > 0) {
					return true;
				}
				try {
					return $dialect->rollBack();
				} catch (\PDOException $e) {
					$this->error = $e->getMessage();
				} finally {
					$this->transLevel = 0;
					$this->commitType = null;
				}
			}
		}

		return false;
	}

	/**
	 * 是否在事务里.
	 *
	 * @return bool
	 */
	public function inTrans(): bool {
		if ($this->dialect) {
			return $this->dialect->inTransaction();
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
	public function trans(\Closure $trans, string &$error = null, ILock $lock = null) {
		try {
			$rst = $this->start();
			if ($rst) {
				try {
					$data = false;
					if ($lock) {
						$data = $lock->lock();
						if ($data === false) {
							throw new \Exception('Cannot lock');
						}
					}
					if ($lock) {
						$rst = $trans($this, $data);
					} else {
						$rst = $trans($this);
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
	 * @return bool
	 */
	public function exec(string $sql): bool {
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
	public function lastInsertId(string $name = null): ?string {
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
	 * @param string      $sql
	 * @param string|null ...$args
	 *
	 * @return int|null
	 */
	public function cud(string $sql, ?string ...$args): ?int {
		if (is_null($this->dialect)) {
			return null;
		}
		$dialect = $this->dialect;
		try {
			// 表前缀处理
			$sql = preg_replace_callback('#\{[a-z][a-z0-9_].*\}#i', function ($r) use ($dialect) {
				return $dialect->getTableName($r[0]);
			}, $sql);
			if ($args) {
				// 参数处理
				$params = 0;
				$sql    = preg_replace_callback('#%(s|d|f)#', function ($r) use (&$params, $args, $dialect, $sql) {
					if ($r[1] == 'f') {
						$v = floatval($args[ $params ]);
					} else if ($r[1] == 'd') {
						$v = intval($args[ $params ]);
					} else if (is_null($args[ $params ])) {
						$v = null;
					} else {
						$v = $dialect->quote($args[ $params ], \PDO::PARAM_STR);
					}
					$params++;

					return $v;
				}, $sql);
			}
			$rst = $dialect->exec($sql);

			return $rst === false ? null : $rst;

		} catch (\Exception $e) {
			$this->error = $e->getMessage();
			log_error($e->getMessage(), 'sql.err');
		}

		return null;
	}

	/**
	 * 删除0行也算成功.
	 *
	 * @param string      $sql
	 * @param string|null ...$args
	 *
	 * @return bool 只要不报错，即使只一行数据未删除也算成功.
	 */
	public function cudx(string $sql, ?string ...$args): bool {
		$rst = $this->cud($sql, ...$args);
		if ($rst === null) {
			return false;
		}

		return true;
	}

	/**
	 *
	 * 执行SQL查询,select a from a where a=%s and %d.
	 *
	 * @param string      $sql
	 * @param string|null ...$args
	 *
	 * @return array
	 */
	public function query(string $sql, ?string ...$args): array {
		$rst = $this->fetch($sql, ...$args);
		if ($rst) {
			$result = $rst->fetchAll(\PDO::FETCH_ASSOC);
			$rst->closeCursor();

			return $result;
		}

		return [];
	}

	/**
	 * 执行SQL查询,select a from a where a=%s and %d.
	 *
	 * @param string      $sql
	 * @param string|null ...$args
	 *
	 * @return null|\PDOStatement
	 */
	public function fetch(string $sql, ?string ...$args): ?\PDOStatement {
		if (is_null($this->dialect)) {
			return null;
		}
		$dialect = $this->dialect;
		try {
			// 表前缀处理
			$sql = preg_replace_callback('#\{[a-z][a-z0-9_].*\}#i', function ($r) use ($dialect) {
				return $dialect->getTableName($r[0]);
			}, $sql);
			if ($args) {
				$params = 0;
				$sql    = preg_replace_callback('#%(s|d|f)#', function ($r) use (&$params, $args, $dialect, $sql) {
					if ($r[1] == 'f') {
						$v = floatval($args[ $params ]);
					} else if ($r[1] == 'd') {
						$v = intval($args[ $params ]);
					} else if (is_null($args[ $params ])) {
						$v = null;
					} else {
						$v = $dialect->quote($args[ $params ], \PDO::PARAM_STR);
					}
					$params++;

					return $v;
				}, $sql);
			}
			//查询
			$rst = $this->dialect->query($sql);
			if ($rst) {
				return $rst;
			}
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
			log_error($e->getMessage(), 'sql.err');
		}

		return null;
	}

	/**
	 *
	 * 执行SQL查询且只取一条记录,select a from a where a=%s and %d.
	 *
	 * @param string      $sql
	 * @param string|null ...$args
	 *
	 * @return array
	 */
	public function queryOne(string $sql, ?string ...$args): array {
		if (!preg_match('/.+\s+LIMIT\s+((%[ds]|0|[1-9]\d*)\s*,\s*)?1\s*$/i', $sql)) {
			$sql = $sql . ' LIMIT 0,1';
		}
		$rst = $this->fetch($sql, ...$args);
		if ($rst) {
			$result = $rst->fetch(\PDO::FETCH_ASSOC);
			$rst->closeCursor();

			return $result ? $result : [];
		}

		return [];
	}

	/**
	 * 查询.
	 *
	 * @param string|\wulaphp\db\sql\ImmutableValue|Query ...$fields
	 *
	 * @return \wulaphp\db\sql\Query
	 */
	public function select(...$fields): Query {
		$sql = new Query(...$fields);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 更新.
	 *
	 * @param string ...$table
	 *
	 * @return \wulaphp\db\sql\UpdateSQL
	 */
	public function update(string ...$table): UpdateSQL {
		$sql = new UpdateSQL($table);
		$sql->setDialect($this->dialect);

		return $sql;
	}

	/**
	 * 删除.
	 *
	 * @return \wulaphp\db\sql\DeleteSQL
	 */
	public function delete(): DeleteSQL {
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
	public function insert(array $data, bool $batch = false): InsertSQL {
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
	public function inserts(array $datas): InsertSQL {
		return $this->insert($datas, true);
	}

	/**
	 * 取表名.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getTableName(string $name): string {
		if ($this->dialect) {
			return $this->dialect->getTableName($name);
		}

		return $name;
	}
}