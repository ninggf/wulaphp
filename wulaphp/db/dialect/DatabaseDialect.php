<?php

namespace wulaphp\db\dialect;

use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\DialectException;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;

/**
 * deal with the difference between various databases
 *
 * @author guangfeng.ning
 *
 */
abstract class DatabaseDialect extends \PDO {
	protected      $cfGname         = '';
	protected      $tablePrefix     = '';
	protected      $charset         = 'UTF8';
	private static $INSTANCE        = [];
	private static $cfgOptions      = [];
	public static  $lastErrorMassge = null;

	public function __construct($options) {
		list ($dsn, $user, $passwd, $attr) = $this->prepareConstructOption($options);
		if (!isset ($attr [ \PDO::ATTR_EMULATE_PREPARES ])) {
			$attr [ \PDO::ATTR_EMULATE_PREPARES ] = false;
		}
		parent::__construct($dsn, $user, $passwd, $attr);
		$this->tablePrefix = isset ($options ['prefix']) && !empty ($options ['prefix']) ? $options ['prefix'] : '';
	}

	/**
	 * get the database dialect by the $name
	 *
	 * @param DatabaseConfiguration $options
	 *
	 * @return DatabaseDialect
	 * @throws DialectException
	 */
	public final static function getDialect($options = null) {
		try {
			if (!$options instanceof DatabaseConfiguration) {
				$options = new DatabaseConfiguration('', $options);
			}
			if (defined('ARTISAN_TASK_PID')) {
				$pid = @posix_getpid();
			} else {
				$pid = 0;
			}
			$name = $options->__toString();
			if (!isset(self::$INSTANCE[ $pid ]) || !array_key_exists($name, self::$INSTANCE[ $pid ])) {
				self::$INSTANCE [ $pid ][ $name ] = null;
				$driver                           = isset ($options ['driver']) && !empty ($options ['driver']) ? $options ['driver'] : 'MySQL';
				$driverClz                        = 'wulaphp\db\dialect\\' . $driver . 'Dialect';
				if (!is_subclass_of($driverClz, 'wulaphp\db\dialect\DatabaseDialect')) {
					throw new DialectException('the dialect ' . $driverClz . ' is not found!');
				}
				/**@var \wulaphp\db\dialect\DatabaseDialect $dr */
				$dr = new $driverClz ($options);
				$dr->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				$dr->onConnected();
				$dr->cfGname                     = $name;
				self::$cfgOptions[ $name ]       = $options;
				self::$lastErrorMassge           = false;
				self::$INSTANCE[ $pid ][ $name ] = $dr;
			}

			return self::$INSTANCE [ $pid ][ $name ];
		} catch (\PDOException $e) {
			log_error($e->getMessage(), 'database');
			throw new DialectException($e->getMessage());
		} catch (DialectException $de) {
			log_error($de->getMessage(), 'database');
			throw $de;
		}
	}

	/**
	 * 重置链接.
	 *
	 * @param string $name
	 *
	 * @return DatabaseDialect
	 * @throws
	 */
	public function reset($name = null) {
		if (defined('ARTISAN_TASK_PID')) {
			$pid = @posix_getpid();
		} else {
			$pid = 0;
		}
		if (!$name) {
			$name = $this->cfGname;
		}
		if (isset(self::$INSTANCE[ $pid ][ $name ])) {
			/**@var \wulaphp\db\dialect\DatabaseDialect $dr */
			$dr = self::$INSTANCE[ $pid ][ $name ];
			try {
				$dr->listDatabases();

				return $dr;
			} catch (\Exception $e) {
				unset(self::$INSTANCE[ $pid ][ $name ]);
			}
		}

		return self::getDialect(self::$cfgOptions[ $name ]);
	}

	public function close($name = null) {
		if (defined('ARTISAN_TASK_PID')) {
			$pid = @posix_getpid();
		} else {
			$pid = 0;
		}
		if (!$name) {
			$name = $this->cfGname;
		}
		if (isset(self::$INSTANCE[ $pid ][ $name ])) {
			unset(self::$INSTANCE[ $pid ][ $name ]);
		}
	}

	/**
	 * get the full table name( prepend the prefix to the $table)
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public function getTableName($table) {
		if (preg_match('#^\{[^\}]+\}.*$#', $table)) {
			return str_replace(['{', '}'], [$this->tablePrefix, ''], $table);
		} else {
			return $table;
		}
	}

	public function getTablePrefix() {
		return $this->tablePrefix;
	}

	/**
	 * get tables from sql.
	 *
	 * @param string $sql
	 *
	 * @return array array('tables'=>array(),'views'=>array()) name array of tables and views defined in sql.
	 */
	public function getTablesFromSQL($sql) {
		$p      = '/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?([^\(]+)/mi';
		$tables = [];
		$views  = [];
		if (preg_match_all($p, $sql, $ms, PREG_SET_ORDER)) {
			foreach ($ms as $m) {
				if (count($m) == 3) {
					$table = $m [2];
				} else {
					$table = $m [1];
				}
				if ($table) {
					$table     = trim(trim($table, '` '));
					$tables [] = str_replace('{prefix}', $this->tablePrefix, $table);
				}
			}
		}
		$p = '/CREATE\s+VIEW\s+(IF\s+NOT\s+EXISTS\s+)?(.+?)\s+AS/mi';
		if (preg_match_all($p, $sql, $ms, PREG_SET_ORDER)) {
			foreach ($ms as $m) {
				if (count($m) == 3) {
					$table = $m [2];
				} else {
					$table = $m [1];
				}
				if ($table) {
					$table    = trim(trim($table, '` '));
					$views [] = str_replace('{prefix}', $this->tablePrefix, $table);
				}
			}
		}

		return ['tables' => $tables, 'views' => $views];
	}

	protected function onConnected() {
	}

	public function __toString() {
	}

	/**
	 * get a select SQL for retreiving data from database.
	 *
	 * @param array|string $fields
	 * @param array        $from
	 * @param array        $joins
	 * @param Condition    $where
	 * @param array        $having
	 * @param array        $group
	 * @param array        $order
	 * @param array        $limit
	 * @param BindValues   $values
	 * @param bool         $forupdate
	 *
	 * @return string
	 */
	public abstract function getSelectSQL($fields, $from, $joins, $where, $having, $group, $order, $limit, $values, $forupdate);

	/**
	 * get a select sql for geting the count from database
	 *
	 * @param array|string $field
	 * @param array        $from
	 * @param array        $joins
	 * @param Condition    $where
	 * @param array        $having
	 * @param array        $group
	 * @param BindValues   $values
	 *
	 * @return string
	 */
	public abstract function getCountSelectSQL($field, $from, $joins, $where, $having, $group, $values);

	/**
	 * get the insert SQL
	 *
	 * @param string     $into
	 * @param array      $data
	 * @param BindValues $values
	 *
	 * @return string
	 */
	public abstract function getInsertSQL($into, $data, $values);

	/**
	 * get the update SQL
	 *
	 * @param array      $table
	 * @param array      $data
	 * @param Condition  $where
	 * @param BindValues $values
	 * @param array      $order
	 * @param array      $limit
	 *
	 * @return string
	 */
	public abstract function getUpdateSQL($table, $data, $where, $values, $order, $limit);

	/**
	 * get the delete SQL
	 *
	 * @param string|array $from
	 * @param array        $joins
	 * @param Condition    $where
	 * @param BindValues   $values
	 * @param array        $order
	 * @param array        $limit
	 *
	 * @return string
	 */
	public abstract function getDeleteSQL($from, $joins, $where, $values, $order, $limit);

	/**
	 * list the databases.
	 *
	 * @return array
	 */
	public abstract function listDatabases();

	/**
	 * create a database.
	 *
	 * @param string $database
	 * @param string $charset
	 *
	 * @return bool
	 */
	public abstract function createDatabase($database, $charset);

	/**
	 * get driver name.
	 *
	 * @return string
	 */
	public abstract function getDriverName();

	/**
	 * transfer the char ` to a proper char.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public abstract function sanitize($string);

	/**
	 * get the charset used by this dialect.
	 * @return string the charset used by this dialect。
	 */
	public abstract function getCharset();

	/**
	 * 取WHERE条件字符串.
	 *
	 * @param array      $conditions
	 * @param BindValues $values
	 *
	 * @return string
	 */
	public abstract function buildWhereString($conditions, $values);

	/**
	 * prepare the construct option, the return must be an array, detail listed following:
	 * 1.
	 * dsn
	 * 2. username
	 * 3. password
	 * 4. attributes
	 *
	 * @param array $options
	 *
	 * @return array array ( dsn, user,passwd, attr )
	 */
	protected abstract function prepareConstructOption($options);
}