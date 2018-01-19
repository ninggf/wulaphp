<?php

namespace wulaphp\db;

use wulaphp\app\App;
use wulaphp\db\sql\Query;
use wulaphp\db\sql\QueryBuilder;
use wulaphp\util\ObjectCaller;
use wulaphp\wulaphp\db\ILock;
use wulaphp\wulaphp\db\TableLocker;

/**
 * View 提供查询等与修改无关的操作.
 *
 * @package wulaphp\mvc\model
 * @author  Leo Ning <windywany@gmail.com>
 * @method static Query sget($id, $fields = '*')  for get
 * @method static Query sfind($where = null, $fields = null, $limit = 10, $start = 0)
 * @method static Query sfindAll($where = null, $fields = null)
 * @method static array smap($where, $valueField, $keyField = null, $rows = [])
 * @method static int scount($con, $id = null)
 * @method static bool sexist($con, $id = null)
 * @method static Query sselect(...$fileds)
 * @method static ILock slock($con)
 */
abstract class View {
	private static $tableClzs   = [];
	public         $table       = '';//表名
	private        $originTable;
	protected      $tableName;//带前缀表名
	protected      $qualifiedName;//带AS的表名
	protected      $primaryKeys = ['id'];
	protected      $errors      = null;
	protected      $lastSQL     = null;
	protected      $lastValues  = null;
	protected      $dumpSQL     = null;
	protected      $alias       = null;
	protected      $foreignKey  = null;//本表主键在其它表中的引用字段
	protected      $primaryKey  = null;//本表主键字段
	/**
	 * @var \wulaphp\db\dialect\DatabaseDialect 数据库链接（PDO）
	 */
	protected $dialect = null;
	protected $dbconnection;
	/**
	 * @var string 查询字段
	 */
	protected $defaultQueryFields = '*';

	/**
	 * 创建模型实例.
	 *
	 * @param string|array|DatabaseConnection|View $db 数据库实例.
	 */
	public function __construct($db = null) {
		if ($this->table !== null) {
			$tb          = explode("\\", get_class($this));
			$this->alias = preg_replace('#(View|Table|Model|Form)$#', '', array_pop($tb));
			if (!$this->table) {
				$table = $this->myTableName();
				if (!$table) {
					$table = lcfirst($this->alias);
				}
				$this->table = preg_replace_callback('#[A-Z]#', function ($r) {
					return '_' . strtolower($r [0]);
				}, $table);
			}
			$this->foreignKey  = $this->table . '_id';//被其它表引用时的字段名
			$this->primaryKey  = empty($this->primaryKeys) ? 'id' : $this->primaryKeys[0];//本表主键字段
			$this->originTable = $this->table;
			$this->table       = '{' . $this->table . '}';

			if ($db instanceof View) {
				$this->dbconnection = $db->dbconnection;
			} else if (!$db instanceof DatabaseConnection) {
				try {
					$this->dbconnection = App::db($db === null ? 'default' : $db);
				} catch (\Exception $e) {
					//无法连接数据库
					log_error($e->getMessage(), 'sql');

					return;
				}
			} else {
				$this->dbconnection = $db;
			}
			$this->dialect       = $this->dbconnection->getDialect();
			$this->tableName     = $this->dialect->getTableName($this->table);
			$this->qualifiedName = $this->table . ' AS ' . $this->alias;
		}
	}

	/**
	 * @param string $name function with s
	 * @param        $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments) {
		$clz = static::class;
		if (!isset(self::$tableClzs[ $clz ])) {
			self::$tableClzs[ $clz ] = new $clz();
		}

		return ObjectCaller::callObjMethod(self::$tableClzs[ $clz ], substr($name, 1), $arguments);
	}

	/**
	 * 指定此表的别名.
	 *
	 * @param string $alias
	 *
	 * @return $this
	 */
	public function alias($alias) {
		$this->alias         = $alias;
		$this->qualifiedName = $this->table . ' AS ' . $this->alias;

		return $this;
	}

	/**
	 * 取一条记录.
	 *
	 * @param int|array $id
	 * @param string    $fields 字段,默认为*.
	 *
	 * @return Query 记录.
	 */
	public function get($id, $fields = '*') {
		if (is_array($id)) {
			$where = $id;
		} else {
			$idf   = $this->primaryKey;
			$where = [$idf => $id];
		}
		$sql = $this->select($fields);
		$sql->where($where)->limit(0, 1);

		return $sql;
	}

	/**
	 * 将json格式的字段值解析为array.
	 *
	 * @param int|array $id    主键或条件.
	 * @param string    $field 字段.
	 *
	 * @return array
	 */
	public function json_decode($id, $field) {
		$rtn = $this->get($id, $field)[ $field ];
		if ($rtn) {
			$rtn = @json_decode($rtn, true);
			if ($rtn) {
				return $rtn;
			}
		}

		return [];
	}

	/**
	 * 获取列表.
	 *
	 * @param array       $where  条件.
	 * @param array|mixed $fields 字段或字段数组.
	 * @param int|null    $limit  取多少条数据，默认10条.
	 * @param int         $start  开始位置
	 *
	 * @return Query 列表查询.
	 */
	public function find($where = null, $fields = null, $limit = 10, $start = 0) {
		$sql = $this->select($fields);
		if ($where) {
			$sql->where($where);
		}
		if ($limit) {
			$sql->limit($start, $limit);
		}

		return $sql;
	}

	/**
	 * 获取全部数据列表.
	 *
	 * @param array       $where  条件.
	 * @param array|mixed $fields 字段或字段数组.
	 *
	 * @return Query
	 */
	public function findAll($where = null, $fields = null) {
		return $this->find($where, $fields, 0);
	}

	/**
	 * 获取key/value数组.
	 *
	 * @param array  $where      条件.
	 * @param string $valueField value字段.
	 * @param string $keyField   key字段.
	 * @param array  $rows       初始数组.
	 *
	 * @return array 读取后的数组.
	 */
	public function map($where, $valueField, $keyField = null, $rows = []) {
		$sql = $this->select($valueField, $keyField);
		$sql->where($where);
		$rst = $sql->toArray($valueField, $keyField, $rows);
		$this->checkSQL($sql);

		return $rst;
	}

	/**
	 * 符合条件的记录总数.
	 *
	 * @param array  $con 条件.
	 * @param string $id  字段用于count的字段,默认为*.
	 *
	 * @return int 记数.
	 */
	public function count($con, $id = null) {
		$sql = $this->select();
		$sql->where($con);
		if ($id) {
			return $sql->count($id);
		} else if (count($this->primaryKeys) == 1) {
			return $sql->count($this->primaryKey);
		} else {
			return $sql->count('*');
		}
	}

	/**
	 * 是否存在满足条件的记录.
	 *
	 * @param array  $con 条件.
	 * @param string $id  字段.
	 *
	 * @return boolean 有记数返回true,反之返回false.
	 */
	public function exist($con, $id = null) {
		return $this->count($con, $id) > 0;
	}

	/**
	 * 查询.
	 *
	 * @param array ...$fileds 字段.
	 *
	 * @return Query
	 */
	public function select(...$fileds) {
		if (empty($fileds) || !isset($fileds[0])) {
			if (isset($this->fields) && $this->fields) {
				$fileds = $this->defaultQueryFields;
			} else {
				$fileds = '*';
			}
		}
		$sql      = new Query($fileds);
		$sql->orm = new Orm($this, $this->primaryKeys[0]);
		$sql->setDialect($this->dialect)->from($this->qualifiedName);

		return $sql;
	}

	/**
	 * 获取数据链接.
	 *
	 * @return \wulaphp\db\DatabaseConnection
	 */
	public function db() {
		return $this->dbconnection;
	}

	/**
	 * 锁定符合条件的行.
	 *
	 * @param string|int|array $con 条件中一定要有主键或唯一索引.
	 *
	 * @return ILock
	 * @throws \Exception
	 */
	public function lock($con) {
		if (!$con) {
			throw_exception(__('no lock condition'));
		}

		if ($con && !is_array($con)) {
			$con = [$this->primaryKeys[0] => $con];
		}

		$query = $this->select('*')->where($con);

		return new TableLocker($query);
	}

	/**
	 * 获取错误信息.
	 *
	 * @return string|array 错误信息.
	 */
	public function lastError() {
		return $this->errors;
	}

	/**
	 * 出错的SQL语句.
	 *
	 * @return string
	 */
	public function lastSQL() {
		return $this->lastSQL;
	}

	/**
	 * 出错SQL值.
	 *
	 * @return mixed 出错的SQL变量值.
	 */
	public function lastSQLValues() {
		return $this->lastValues;
	}

	/**
	 * @return string 表名
	 */
	protected function myTableName() {
		return null;
	}

	/**
	 * one-to-one.
	 *
	 * @param View|string $tableCls
	 * @param string      $foreign_key 值字段在$tableCls中的引用.
	 * @param string      $value_key   值字段，默认为本表主键.
	 *
	 * @return array
	 */
	protected final function hasOne($tableCls, $foreign_key = '', $value_key = '') {
		if (!$tableCls instanceof View) {
			$tableCls = new SimpleTable($tableCls, $this->dbconnection);
		}

		if (!$foreign_key) {
			$foreign_key = $this->foreignKey;
		}

		if (!$value_key) {
			$value_key = $this->primaryKey;
		}

		$sql = $tableCls->select();

		return [$sql, $foreign_key, $value_key, true, 'hasOne'];
	}

	/**
	 * one-to-many.
	 *
	 * @param View|string $tableCls
	 * @param string      $foreign_key 值字段在$tableCls中的引用.
	 * @param string      $value_key   值字段，默认为本表主键.
	 *
	 * @return array
	 */
	protected final function hasMany($tableCls, $foreign_key = '', $value_key = '') {
		if (!$tableCls instanceof View) {
			$tableCls = new SimpleTable($tableCls, $this->dbconnection);
		}
		if (!$foreign_key) {
			$foreign_key = $this->foreignKey;
		}

		if (!$value_key) {
			$value_key = $this->primaryKey;
		}

		$sql = $tableCls->select();

		return [$sql, $foreign_key, $value_key, false, 'hasMany'];
	}

	/**
	 * many-to-many(只能是主键相关).
	 *
	 * @param View|string  $tableCls
	 * @param string       $mtable     中间表名
	 * @param string|array $value_keys 当前表在$mtable中的外键（本表主键）.
	 * @param string|array $table_keys $tableCls在$mtable中的外键($table的主键).
	 *
	 * @return array|null
	 */
	protected final function belongsToMany($tableCls, $mtable = '', $value_keys = '', $table_keys = '') {
		if (!$tableCls instanceof View) {
			$tableCls = new SimpleTable($tableCls, $this->dbconnection);
		}

		$tableCls->alias('MTB');

		if (!$value_keys) {
			$value_keys = $this->foreignKey;
		}

		if (is_array($value_keys)) {
			$myPkf      = $value_keys[1];
			$value_keys = $value_keys[0];
		} else {
			$myPkf = $this->primaryKey;
		}
		// where RTB.$value_keys = MY.$myPkf
		$value_keys = 'RTB.' . $value_keys;

		if (!$table_keys) {
			$table_keys = $tableCls->foreignKey;
		}
		if (is_array($table_keys)) {
			$itPkf      = $table_keys[1];
			$table_keys = $table_keys[0];
		} else {
			$itPkf = $tableCls->primaryKey;
		}
		// JOIN ON RTB.$table_keys = MTB.$itPkf
		$table_keys = 'RTB.' . $table_keys;

		if (!$mtable) {
			$mtables = [$this->originTable, $tableCls->originTable];
			sort($mtables);
			$mtable = implode('_', $mtables);
		}
		// user has roles
		// tableCls = roles AS MTB
		// select MTB.* FROM roles left join user_role ON MTB.id = LTB.role_id WHERE LTB.user_id = ?
		$sql = $tableCls->select('MTB.*')->inner('{' . $mtable . '} AS RTB', 'MTB.' . $itPkf, $table_keys);

		return [$sql, $value_keys, $myPkf, false, 'belongsToMany'];

	}

	/**
	 * one-to-one and one-to-many inverse
	 *
	 *
	 * @param View|string $tableCls
	 * @param string      $value_key   值字段(本表通过此字段属于$tableCls),默认为$tableCls_id.
	 * @param string      $foreign_key $tableCls的主键或唯一键.
	 *
	 * @return array
	 */
	protected final function belongsTo($tableCls, $value_key = '', $foreign_key = '') {
		if (!$tableCls instanceof View) {
			$tableCls = new SimpleTable($tableCls, $this->dbconnection);
		}

		if (!$foreign_key) {
			$foreign_key = $tableCls->primaryKey;
		}

		if (!$value_key) {
			$value_key = $tableCls->foreignKey;
		}

		$sql = $tableCls->select();

		return [$sql, $foreign_key, $value_key, false, 'belongsTo'];
	}

	/**
	 * 检测SQL执行.
	 *
	 * @param QueryBuilder $sql
	 */
	protected final function checkSQL(QueryBuilder $sql) {
		$this->errors = $sql->lastError();
		if ($this->errors) {
			$this->lastSQL = $sql->lastSQL();
		} else {
			$this->lastSQL = $sql->getSqlString();
		}
		$this->lastValues = $sql->lastValues();
		$this->dumpSQL    = $sql->dumpSQL();
	}

	/**
	 * 从数据中根据主键获取条件.
	 *
	 * @param array $data 数据.
	 *
	 * @return array 条件.
	 */
	protected final function getWhere($data) {
		$con = [];
		foreach ($this->primaryKeys as $f) {
			if (isset ($data [ $f ])) {
				$con [ $f ] = $data [ $f ];
			}
		}

		return $con;
	}
}