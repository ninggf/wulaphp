<?php

namespace wulaphp\db;

use wulaphp\app\App;
use wulaphp\db\sql\Query;
use wulaphp\db\sql\QueryBuilder;

/**
 * View 提供查询等与修改无关的操作.
 *
 * @package wulaphp\mvc\model
 * @author  Leo Ning <windywany@gmail.com>
 */
abstract class View {
	public    $table       = null;
	protected $tableName;
	protected $qualifiedName;
	protected $primaryKeys = ['id'];
	/**
	 * @var \wulaphp\db\dialect\DatabaseDialect
	 */
	protected $dialect    = null;
	protected $errors     = null;
	protected $lastSQL    = null;
	protected $lastValues = null;
	protected $dumpSQL    = null;
	protected $alias      = null;

	/**
	 * 创建模型实例.
	 *
	 * @param string|array|DatabaseConnection $db 数据库实例.
	 */
	public function __construct($db = null) {
		$tb          = explode("\\", get_class($this));
		$this->alias = preg_replace('#(View|Table)$#', '', array_pop($tb));
		if (!$this->table) {
			$table       = lcfirst($this->alias);
			$this->table = preg_replace_callback('#[A-Z]#', function ($r) {
				return '_' . strtolower($r [0]);
			}, $table);
		}
		$this->table = '{' . $this->table . '}';
		if (!$db instanceof DatabaseConnection) {
			$db = App::db($db === null ? 'default' : $db);
		}
		$this->dialect       = $db->getDialect();
		$this->tableName     = $this->dialect->getTableName($this->table);
		$this->qualifiedName = $this->table . ' AS ' . $this->alias;
	}

	/**
	 * 取一条记录.
	 *
	 * @param int|array $id
	 * @param string    $fields 字段,默认为*.
	 *
	 * @return array 记录.
	 */
	public function get($id, $fields = '*') {
		if (is_array($id)) {
			$where = $id;
		} else {
			$idf   = empty($this->primaryKeys) ? 'id' : $this->primaryKeys[0];
			$where = [$idf => $id];
		}
		$sql = $this->select($fields);
		$sql->where($where)->limit(0, 1);
		$rst = $sql->get();
		$this->checkSQL($sql);

		return $rst;
	}

	/**
	 * 获取列表.
	 *
	 * @param array|mixed $fields 字段或字段数组.
	 * @param array       $where  条件.
	 * @param int         $start
	 * @param int|null    $limit  取多少条数据，默认10条.
	 *
	 * @return array 读取后的数组.
	 */
	public function find($fields, $where, $start = 0, $limit = 10) {
		$sql = $this->select($fields);
		$sql->where($where);
		if ($limit) {
			$sql->limit($start, $limit);
		}
		$rst = $sql->toArray();
		$this->checkSQL($sql);

		return $rst;
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
			return $sql->count($this->primaryKeys [0]);
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
		$sql = new Query($fileds);
		$sql->setDialect($this->dialect)->from($this->qualifiedName);

		return $sql;
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
	 * 检测SQL执行.
	 *
	 * @param QueryBuilder $sql
	 */
	protected function checkSQL(QueryBuilder $sql) {
		$this->errors     = $sql->lastError();
		$this->lastSQL    = $sql->lastSQL();
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
	protected function getWhere($data) {
		$con = [];
		foreach ($this->primaryKeys as $f) {
			if (isset ($data [ $f ])) {
				$con [ $f ] = $data [ $f ];
			}
		}

		return $con;
	}
}