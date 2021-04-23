<?php

namespace wulaphp\db;

use wulaphp\app\App;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;
use wulaphp\db\sql\Query;
use wulaphp\db\sql\QueryBuilder;
use wulaphp\util\ObjectCaller;
use wulaphp\util\TraitObject;

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
abstract class View extends TraitObject {
    /**@var \wulaphp\db\View[] */
    private static $tableClzs   = [];
    protected      $table       = '';//表名
    protected      $originTable;
    protected      $tableName;//带前缀表名
    protected      $qualifiedName;//带AS的表名
    protected      $primaryKeys = ['id'];
    protected      $errors      = '';
    protected      $lastSQL     = null;
    protected      $lastValues  = null;
    protected      $dumpSQL     = null;
    protected      $alias       = null;
    private        $oalias      = null;
    protected      $foreignKey  = null;//本表主键在其它表中的引用字段
    protected      $primaryKey  = null;//本表主键字段
    /**
     * @var \wulaphp\db\dialect\DatabaseDialect 数据库链接（PDO）
     */
    protected $dialect = null;
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected $dbconnection;
    /**
     * @var string 查询字段
     */
    protected $defaultQueryFields = '*';

    /**
     * 创建模型实例.
     *
     * @param string|array|DatabaseConnection|View|null $db 数据库实例.
     */
    public function __construct($db = null) {
        if ($this->table !== null) {
            if ($db instanceof View) {
                $this->dbconnection = $db->dbconnection;
            } else if (!$db instanceof DatabaseConnection) {
                $this->dbconnection = App::db($db === null ? 'default' : $db);
            } else {
                $this->dbconnection = $db;
            }
            $tb           = explode("\\", get_class($this));
            $this->oalias = $this->alias = preg_replace('#(View|Table|Model|Form|Entity)$#', '', array_pop($tb));
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
            $this->primaryKey  = empty($this->primaryKeys) ? '' : $this->primaryKeys[0];//本表主键字段
            $this->originTable = $this->table;
            $this->table       = '{' . $this->table . '}';
            if ($this->dbconnection) {
                $this->dialect   = $this->dbconnection->getDialect();
                $this->tableName = $this->dialect->getTableName($this->table);
            }
            $this->qualifiedName = $this->table . ' AS ' . $this->alias;
        }

        parent::__construct('wulaphp\db\View', 'wulaphp\db\Table');
    }

    /**
     * 静态魔术方法.
     *
     * @param string $name function with s
     * @param        $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $name, $arguments) {
        $clz = static::class;
        try {
            if (!isset(self::$tableClzs[ $clz ])) {
                self::$tableClzs[ $clz ] = new $clz();
            } else {
                $cfg = self::$tableClzs[ $clz ]->db()->getConfig();
                self::$tableClzs[ $clz ]->db(DatabaseConnection::connect($cfg));
            }

            return ObjectCaller::callObjMethod(self::$tableClzs[ $clz ], substr($name, 1), $arguments);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 魔术方法，用于实现关联映射.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $args) {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$args);
        }

        throw new \BadMethodCallException($name . ' does not exist');
    }

    /**
     * 指定此表的别名.
     *
     * @param string|null $alias 为空时重置alias
     *
     * @return $this
     */
    public final function alias(?string $alias = null): View {
        if ($alias) {
            $this->oalias = $this->alias;
            $this->alias  = $alias;
        } else {
            $this->alias = $this->oalias;
        }
        $this->qualifiedName = $this->table . ' AS ' . $this->alias;

        return $this;
    }

    /**
     * 取一条记录.
     *
     * @param int|array                                   $id
     * @param array|string|\wulaphp\db\sql\ImmutableValue $fields 字段,默认为*.
     *
     * @return Query 记录.
     * @since      1.0.0
     * @deprecated 使用findOne, 将在4.0版本中移除。
     */
    public final function get($id, $fields = '*'): Query {
        if (is_array($id)) {
            $where = $id;
        } else {
            $idf   = $this->primaryKey;
            $where = [$idf => $id];
        }
        if (is_array($fields)) {
            $sql = $this->select(...$fields);
        } else {
            $sql = $this->select($fields);
        }
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
    public final function json_decode($id, string $field): array {
        $rtn = $this->findOne($id, $field)[ $field ];
        if ($rtn) {
            $rtn = @json_decode($rtn, true);
            if ($rtn) {
                return $rtn;
            }
        }

        return [];
    }

    /**
     * 直接取一个字段的值.
     *
     * @param string|array $id
     * @param string       $field
     *
     * @return string
     * @since v3.5.9
     */
    public final function fetch($id, string $field): ?string {
        return $this->findOne($id, $field)[ $field ];
    }

    /**
     * 获取列表.
     *
     * @param array|null                                       $where  条件.
     * @param array|string|\wulaphp\db\sql\ImmutableValue|null $fields 字段或字段数组.
     * @param int|null                                         $limit  取多少条数据，默认10条.
     * @param int                                              $start  开始位置
     *
     * @return Query 列表查询.
     */
    public final function find($where = null, $fields = null, $limit = 10, $start = 0): Query {
        if (is_array($fields)) {
            $sql = $this->select(...$fields);
        } else {
            $sql = $this->select($fields);
        }
        if ($where) {
            $sql->where($where);
        }
        if ($limit) {
            $sql->limit($start, $limit);
        }

        return $sql;
    }

    /**
     * 取一条记录.
     *
     * @param int|array                                   $id
     * @param array|string|\wulaphp\db\sql\ImmutableValue $fields 字段,默认为*.
     *
     * @return Query 记录.
     * @since v3.5.9
     */
    public final function findOne($id = 0, string $fields = '*'): Query {
        if (is_array($id)) {
            $where = $id;
        } else if ($id) {
            $idf   = $this->primaryKey;
            $where = [$idf => $id];
        } else {
            $where = [];
        }
        if (is_array($fields)) {
            $sql = $this->select(...$fields);
        } else {
            $sql = $this->select($fields);
        }
        $sql->where($where)->limit(0, 1);

        return $sql;
    }

    /**
     * 获取全部数据列表.
     *
     * @param array|Condition|null                        $where  条件.
     * @param array|string|\wulaphp\db\sql\ImmutableValue $fields 字段或字段数组.
     *
     * @return Query
     */
    public final function findAll($where = null, $fields = '*'): Query {
        return $this->find($where, $fields, 0);
    }

    /**
     * 获取key/value数组.
     *
     * @param array|Condition $where      条件.
     * @param string          $valueField value字段.
     * @param string|null     $keyField   key字段.
     * @param array           $rows       初始数组.
     *
     * @return array 读取后的数组.
     */
    public final function map($where, string $valueField, ?string $keyField = null, array $rows = []): array {
        $sql = $this->select($valueField, $keyField);
        $sql->where($where);
        $rst = $sql->toArray($valueField, $keyField, $rows);
        $this->checkSQL($sql);

        return $rst;
    }

    /**
     * 符合条件的记录总数.
     *
     * @param array|string|int $con 条件.
     * @param string           $id  用于count的字段,默认为*.
     *
     * @return int 记数.
     */
    public final function count($con, string $id = '*'): int {
        if ($con && !is_array($con)) {
            $con = [$this->primaryKeys[0] => $con];
        }
        $sql = $this->select();
        $sql->where($con);

        return $sql->count($id);
    }

    /**
     * 是否存在满足条件的记录.
     *
     * @param array|string|int $con 条件.
     * @param string           $id  字段,请保持默认的*.
     *
     * @return boolean 有记数返回true,反之返回false.
     */
    public final function exist($con, string $id = '*'): bool {
        return $this->count($con, $id) > 0;
    }

    /**
     * 基于自增的ID主键对大表进行遍历。示例代码如下:
     * <code>
     *  $m = new UserModel();
     *  foreach ($m->traverse(['status'=>1],500,'uid,nickname','uid')){
     *      // your codes here
     *  }
     * </code>
     *
     * @param array  $con    条件,可以通过$id对应的字段指定启始id。
     * @param int    $num    每次读取多少条记录
     * @param string $fields 字段
     * @param string $id     自增主键字段
     *
     * @return \Generator
     */
    public final function traverse(array $con = [], int $num = 100, string $fields = '*', string $id = 'id'): \Generator {
        if (isset($con[ $id ][0])) {
            $rst[0]['minId'] = $con[ $id ][0];
            if (isset($con[ $id ][1])) {
                $rst[0]['maxId'] = $con[ $id ][1];
            } else {
                $ids = "SELECT MAX($id) AS maxId FROM " . $this->tableName;
                $mst = $this->dbconnection->queryOne($ids);
                if ($mst) {
                    $rst[0]['maxId'] = $mst['maxId'];
                } else {
                    $rst = null;
                }
            }
            unset($con[ $id ]);
        } else {
            $ids = "SELECT MIN($id) AS minId,MAX($id) AS maxId FROM " . $this->tableName;
            $rst = $this->dbconnection->query($ids);
        }
        if ($rst) {
            $minId = $rst[0]['minId'];
            $maxId = $rst[0]['maxId'] + 1; #一定要加1
            $vs    = new BindValues();
            if ($con) {
                $where = (new Condition($con))->getWhereCondition($this->dialect, $vs);
                $sql   = "SELECT $fields FROM  $this->tableName WHERE $where AND $id >= :minId AND $id < :maxId";
                $stmt  = $this->dialect->prepare($sql);
                if (!$stmt) {
                    return;
                }
                foreach ($vs as $value) {
                    [$name, $val, $type] = $value;
                    if (!$stmt->bindValue($name, $val, $type)) {
                        return;
                    }
                }
            } else {
                $sql  = "SELECT $fields FROM  $this->tableName WHERE $id >= :minId AND $id < :maxId";
                $stmt = $this->dialect->prepare($sql);
                if (!$stmt) {
                    return;
                }
            }
            $gap = max(1, intval($num));
            while ($minId < $maxId) {
                $sid   = $minId;
                $minId = min($minId + $gap, $maxId);

                $stmt->bindValue(':minId', $sid);
                $stmt->bindValue(':maxId', $minId);
                $results = $stmt->execute();
                if ($results && $stmt->rowCount() > 0) {
                    while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
                        yield $row;
                    }
                    if (!$stmt->closeCursor()) {
                        break;
                    }
                }
            }
            unset($stmt);
        }
    }

    /**
     * 查询.
     *
     * @param string|null ...$fileds 字段.
     *
     * @return Query
     */
    public final function select(?string ...$fileds): Query {
        return $this->_select(true, ...$fileds);
    }

    /**
     * (设置并)获取数据链接.
     *
     * @param \wulaphp\db\DatabaseConnection|null $con
     *
     * @return \wulaphp\db\DatabaseConnection
     */
    public final function db(?DatabaseConnection $con = null): ?DatabaseConnection {
        if ($con) {
            $this->dbconnection = $con;
            $this->dialect      = $this->dbconnection->getDialect();
        }

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
    public final function lock($con): ILock {
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
    public function lastSQL(): string {
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
    protected function myTableName(): ?string {
        return null;
    }

    /**
     * one-to-one.
     *
     * @param View|string $tableCls
     * @param string      $foreign_key $tableCls中的外键字段.
     * @param string      $local_key   本表字段(默认为本表主键).
     *
     * @return array
     */
    protected final function hasOne($tableCls, string $foreign_key = '', string $local_key = ''): array {
        if (!$tableCls instanceof View) {
            $tableCls = new SimpleTable($tableCls, $this->dbconnection);
        }

        if (!$foreign_key) {
            $foreign_key = $this->foreignKey;
        }

        if (!$local_key) {
            $local_key = $this->primaryKey;
        }

        $sql = $tableCls->select();

        return [
            $sql,//查询数据的SQL
            $foreign_key,
            $local_key,
            true,
            'hasOne',
            $tableCls->_select(false)->where([
                $tableCls->alias . '.' . $foreign_key => imv($this->alias . '.' . $local_key)
            ]) // exist 过滤查询语句
        ];
    }

    /**
     * one-to-many.
     *
     * @param View|string $tableCls
     * @param string      $foreign_key $tableCls中的外键字段.
     * @param string      $local_key   本表字段(默认为本表主键).
     *
     * @return array
     */
    protected final function hasMany($tableCls, string $foreign_key = '', string $local_key = ''): array {
        if (!$tableCls instanceof View) {
            $tableCls = new SimpleTable($tableCls, $this->dbconnection);
        }
        if (!$foreign_key) {
            $foreign_key = $this->foreignKey;
        }

        if (!$local_key) {
            $local_key = $this->primaryKey;
        }

        $sql = $tableCls->select();

        return [
            $sql,
            $foreign_key,
            $local_key,
            false,
            'hasMany',
            $tableCls->_select(false)->where([
                $tableCls->alias . '.' . $foreign_key => imv($this->alias . '.' . $local_key)
            ])
        ];
    }

    /**
     * many-to-many(只能是主键相关).
     *
     * @param View|string  $tableCls
     * @param string       $mtable      中间表
     * @param string|array $local_key   当前表在中间表关联字段(默认为['table_id','pk']).
     * @param string|array $foreign_key $tableCls在中间表的关联字段(默认为['table_id','pk']).
     *
     * @return array
     */
    protected final function belongsToMany($tableCls, string $mtable = '', $local_key = '', $foreign_key = ''): array {
        if (!$tableCls instanceof View) {
            $tableCls = new SimpleTable($tableCls, $this->dbconnection);
        }

        $tableCls->alias('TB');//右表

        if (!$local_key) {
            $local_key = $this->foreignKey;
        }

        if (is_array($local_key)) {
            $myPkf     = $local_key[1];
            $local_key = $local_key[0];
        } else {
            $myPkf = $this->primaryKey;
        }
        // where MTB.$value_keys = MY.$myPkf
        $local_key = 'MTB.' . $local_key;

        if (!$foreign_key) {
            $foreign_key = $tableCls->foreignKey;
        }
        if (is_array($foreign_key)) {
            $itPkf       = $foreign_key[1];
            $foreign_key = $foreign_key[0];
        } else {
            $itPkf = $tableCls->primaryKey;
        }
        // JOIN ON MTB.$table_keys = TB.$itPkf
        $foreign_key = 'MTB.' . $foreign_key;
        // 中间表
        if (!$mtable) {
            $mtables = [$this->originTable, $tableCls->originTable];
            sort($mtables);
            $mtable = implode('_', $mtables);
        }
        // user has roles
        // tableCls = roles AS TB
        // select TB.* FROM roles inner join user_role ON TB.id = LTB.role_id WHERE LTB.user_id = ?
        $sql  = $tableCls->select('TB.*')->inner('{' . $mtable . '} AS MTB', 'TB.' . $itPkf, $foreign_key);
        $sql2 = $tableCls->_select(false, 'TB.*')->inner('{' . $mtable . '} AS MTB', 'TB.' . $itPkf, $foreign_key)->where([$local_key => imv($this->alias . '.' . $myPkf)]);

        return [$sql, $local_key, $myPkf, false, 'belongsToMany', $sql2];
    }

    /**
     * one-to-one and one-to-many inverse
     *
     *
     * @param View|string $tableCls
     * @param string      $local_key   本表外键字段
     * @param string      $foreign_key $tableCls表中的关联字段(默认为其主键).
     *
     * @return array
     */
    protected final function belongsTo($tableCls, string $local_key = '', string $foreign_key = ''): array {
        if (!$tableCls instanceof View) {
            $tableCls = new SimpleTable($tableCls, $this->dbconnection);
        }

        if (!$foreign_key) {
            $foreign_key = $tableCls->primaryKey;
        }

        if (!$local_key) {
            $local_key = $tableCls->foreignKey;
        }

        $sql = $tableCls->select();

        return [
            $sql,
            $foreign_key,
            $local_key,
            true,
            'belongsTo',
            $tableCls->_select(false)->where([
                $tableCls->alias . '.' . $foreign_key => imv($this->alias . '.' . $local_key)
            ])
        ];
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
        $this->dumpSQL    = $sql->getSqlString();
    }

    /**
     * 从数据中根据主键获取条件.
     *
     * @param array $data 数据.
     *
     * @return array 条件.
     */
    protected final function getWhere(array $data): array {
        $con = [];
        foreach ($this->primaryKeys as $f) {
            if (array_key_exists($f, $data)) {
                if (isset($data[ $f ])) {
                    $con [ $f ] = $data [ $f ];
                } else {
                    $con [ $f . ' $' ] = null;
                }
            }
        }

        return $con;
    }

    /**
     * @param bool        $orm 是否启用orm
     * @param string|null ...$fields
     *
     * @return \wulaphp\db\sql\Query
     */
    private function _select(bool $orm, ?string ...$fields): Query {
        if (empty($fileds) || !isset($fileds[0])) {
            if (isset($this->fields) && $this->fields) {
                $fileds = $this->defaultQueryFields;
            } else {
                $fileds = '*';
            }
        }
        $sql = new Query($fileds);
        if ($this->primaryKey && $orm) {
            $sql->orm = new Orm($this, $this->primaryKey);
        }
        $sql->view = $this;
        $sql->setDialect($this->dialect)->from($this->qualifiedName);

        return $sql;
    }
}