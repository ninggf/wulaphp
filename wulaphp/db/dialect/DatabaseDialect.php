<?php
namespace wulaphp\db\dialect;

use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;

/**
 * deal with the difference between various databases
 *
 * @author guangfeng.ning
 *
 */
abstract class DatabaseDialect extends \PDO {

	private static $INSTANCE = array();

	private       $tablePrefix;
	protected     $charset         = 'UTF8';
	public static $lastErrorMassge = '';

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
	 */
	public final static function getDialect($options = null) {
		try {
			if (!$options instanceof DatabaseConfiguration) {
				$options = new DatabaseConfiguration('', $options);
			}
			$name                  = $options->__toString();
			self::$lastErrorMassge = false;
			if (!isset (self::$INSTANCE [ $name ])) {
				$driver    = isset ($options ['driver']) && !empty ($options ['driver']) ? $options ['driver'] : 'MySQL';
				$driverClz = 'wulaphp\db\dialect\\' . $driver . 'Dialect';
				if (!is_subclass_of($driverClz, 'wulaphp\db\dialect\DatabaseDialect')) {
					trigger_error('the dialect ' . $driverClz . ' is not found!', E_USER_ERROR);
				}
				$dr = new $driverClz ($options);
				$dr->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				$dr->onConnected();
				self::$INSTANCE [ $name ] = $dr;
			}

			return self::$INSTANCE [ $name ];
		} catch (\PDOException $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);

			return null;
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
			return str_replace(array('{', '}'), array($this->tablePrefix, ''), $table);
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
		$tables = array();
		$views  = array();
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

		return array('tables' => $tables, 'views' => $views);
	}

	protected function onConnected() {
	}

	public function __toString() {
	}

	/**
	 * get a select SQL for retreiving data from database.
	 *
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
	public abstract function getSelectSQL($fields, $from, $joins, $where, $having, $group, $order, $limit, $values);

	/**
	 * get a select sql for geting the count from database
	 *
	 * @param array      $field
	 * @param array      $from
	 * @param array      $joins
	 * @param Condition  $where
	 * @param array      $having
	 * @param array      $group
	 * @param BindValues $values
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
	 *
	 * @return string
	 */
	public abstract function getUpdateSQL($table, $data, $where, $values);

	/**
	 * get the delete SQL
	 *
	 * @param string     $from
	 * @param array      $using
	 * @param Condition  $where
	 * @param BindValues $values
	 *
	 * @return string
	 */
	public abstract function getDeleteSQL($from, $using, $where, $values);

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
