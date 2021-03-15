<?php

namespace wulaphp\db;

use wulaphp\db\sql\DeleteSQL;
use wulaphp\db\sql\InsertSQL;
use wulaphp\db\sql\UpdateSQL;

/**
 * 表基类,提供与表相关的简单操作。
 *
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 * @property $_v__formData
 * @property $_f__tableData
 */
abstract class Table extends View {
    protected $autoIncrement = true;

    /**
     * Table constructor.
     *
     * @param string|array|DatabaseConnection|View|null $db
     */
    public function __construct($db = null) {
        parent::__construct($db);
        if ($this->autoIncrement ==true && count($this->primaryKeys) != 1) {
            $this->autoIncrement = false;
        }
        $this->parseTraits();
    }

    /**
     * 在事务中运行.
     *
     * @param \Closure               $fun
     * @param \wulaphp\db\ILock|null $lock
     *
     * @return mixed|null
     */
    public final function trans(\Closure $fun, ?ILock $lock = null) {
        return $this->dbconnection->trans($fun, $this->errors, $lock);
    }

    /**
     * 保存数据。
     *
     * @param array         $data 要保存的数据
     * @param array|null    $con  更新条件
     * @param \Closure|null $cb   数据处理回调
     *
     * @return bool
     */
    public final function save(array $data, $con = null, ?\Closure $cb = null): bool {
        if (!$con) {
            $rst = $this->insert($data, $cb);
        } else if ($this->exist($con)) {//存在即修改
            $rst = $this->update($data, $con, $cb);
        } else {
            $rst = $this->insert($data, $cb);
        }

        if ($rst === false) {
            return false;
        }

        return true;
    }

    /**
     * 创建记录.
     *
     * @param array         $data 数据.
     * @param \Closure|null $cb   数据处理函数.
     *
     * @return bool|int 成功返回true或主键值,失败返回false.
     * @throws
     */
    public final function insert(array $data, ?\Closure $cb = null) {
        if ($cb && $cb instanceof \Closure) {
            $data = $cb ($data, $this);
        }
        if ($data) {
            $this->filterFields($data);
            if (method_exists($this, 'validateNewData')) {
                if (isset($this->_v__formData)) {
                    $this->validateNewData($this->_v__formData);
                } else {
                    $this->validateNewData($data);
                }
            }
            $sql = new InsertSQL($data);
            $sql->into($this->table)->setDialect($this->dialect);
            if ($this->autoIncrement) {
                $rst = $sql->newId($this->primaryKey);
            } else {
                $rst = $sql->exec(true);
            }
            if ($rst) {
                return $rst;
            } else {
                $this->checkSQL($sql);

                return false;
            }
        } else {
            $this->errors = '数据为空.';

            return false;
        }
    }

    /**
     * 新增数据，唯一键冲突时则修改相应的数据,如果使用校验器则使用新增数据校验器.
     *
     * @param array       $data  数据.
     * @param array|null  $data1 修改数据.
     * @param string|null $key   冲突键，默认是主键
     *
     * @return bool
     */
    public final function upsert(array $data, ?array $data1 = null, ?string $key = null): bool {
        if ($data) {
            $this->filterFields($data);
            if (method_exists($this, 'validateNewData')) {
                if (isset($this->_v__formData)) {
                    $this->validateNewData($this->_v__formData);
                } else {
                    $this->validateNewData($data);
                }
            }
            if (!$key) {
                $key = $this->primaryKey;
            }
            assert($key != '', '未定义冲突键名');
            $sql = new InsertSQL($data);
            $sql->into($this->table)->setDialect($this->dialect);
            $sql->onDuplicate($key, $data1 ? $data1 : $data);
            $rst = $sql->exec();//不报错就算OK ^_^
            if ($rst) {
                return $rst;
            } else {
                $this->checkSQL($sql);

                return false;
            }
        } else {
            $this->errors = '数据为空.';

            return false;
        }
    }

    /**
     * 新增数据，唯一键冲突时则修改相应的数据，如果使用校验器则使用新增数据校验器.
     *
     * @param array       $datas 批量数据
     * @param array       $data1 修改操作
     * @param string|null $key   冲突键
     *
     * @return bool
     */
    public final function upserts(array $datas, array $data1, string $key = null): bool {
        if ($datas) {
            if (!$key) {
                $key = $this->primaryKey;
            }
            assert($key != '', '未定义冲突键名');
            assert(!empty($data1), '$data1为空');
            //数据校验
            if (method_exists($this, 'validateNewData')) {
                foreach ($datas as &$data) {
                    $this->filterFields($data);
                    $this->validateNewData($data);
                }
            } else {
                foreach ($datas as &$data) {
                    $this->filterFields($data);
                }
            }

            $sql = new InsertSQL($datas, true);
            $sql->into($this->table)->setDialect($this->dialect);
            $sql->onDuplicate($key, $data1);
            $rst = $sql->exec();
            if ($rst) {
                return $rst;
            } else {
                $this->checkSQL($sql);

                return false;
            }
        } else {
            $this->errors = '数据为空.';

            return false;
        }
    }

    /**
     * 新建记录(insert的别名).
     *
     * @param array         $data 数据
     * @param \Closure|null $cb   数据处理器
     *
     * @return bool|int
     * @see \wulaphp\db\Table::insert()
     */
    public final function create(array $data, \Closure $cb = null) {
        return $this->insert($data, $cb);
    }

    /**
     * 批量插入数据.
     *
     * @param array         $datas 要插入的数据数组.
     * @param \Closure|null $cb
     *
     * @return bool|array 如果配置了自增键将返回自增键值的数组.
     * @throws
     */
    public final function inserts(array $datas, ?\Closure $cb = null) {
        if ($cb && $cb instanceof \Closure) {
            $datas = $cb ($datas, $this);
        }
        if ($datas) {
            if (method_exists($this, 'validateNewData')) {
                foreach ($datas as &$data) {
                    $this->filterFields($data);
                    $this->validateNewData($data);
                }
            } else {
                foreach ($datas as &$data) {
                    $this->filterFields($data);
                }
            }

            $sql = new InsertSQL($datas, true);
            $sql->into($this->table)->setDialect($this->dialect);
            if ($this->autoIncrement) {
                $sql->autoKey($this->primaryKey);
                $rst = $sql->exec();
            } else {
                $rst = $sql->exec(true);
            }
            if ($rst) {
                return $rst;
            } else {
                $this->checkSQL($sql);

                return false;
            }
        } else {
            $this->errors = '数据为空.';

            return false;
        }
    }

    /**
     * 更新数据或获取UpdateSQL实例.
     *
     * @param array|null    $data 数据.
     * @param array|null    $con  更新条件.
     * @param \Closure|null $cb   数据处理器.
     *
     * @return bool|UpdateSQL 成功true，失败false；当$data=null时返回UpdateSQL实例.
     * @throws
     */
    public final function update(array $data = null, $con = null, \Closure $cb = null) {
        if ($data === null) {
            $sql = new UpdateSQL($this->qualifiedName);
            $sql->setDialect($this->dialect);

            return $sql;
        }
        if ($con && !is_array($con)) {
            $con = [$this->primaryKeys[0] => $con];
        }
        if (!$con) {
            $con = $this->getWhere($data);
            if (count($con) != count($this->primaryKeys)) {
                $this->errors = '未提供更新条件';

                return false;
            }
        }
        if (empty ($con)) {
            $this->errors = '更新条件为空';

            return false;
        }
        if ($cb && $cb instanceof \Closure) {
            $data = $cb ($data, $con, $this);
        }
        if ($data) {
            $this->filterFields($data);
            if (method_exists($this, 'validateUpdateData')) {
                if (property_exists($this, '_v__formData') && $this->_v__formData) {
                    $this->validateUpdateData($this->_v__formData);
                } else {
                    $this->validateUpdateData($data);
                }
            }
            $sql = new UpdateSQL($this->table);
            $sql->set($data)->setDialect($this->dialect)->where($con);
            $rst = $sql->exec();
            $this->checkSQL($sql);

            return $rst;
        } else {
            return false;
        }
    }

    /**
     * 删除记录或获取DeleteSQL实例.
     *
     * @param array|int|null $con 条件或主键.
     *
     * @return boolean|DeleteSQL 成功true，失败false；当$con==null时返回DeleteSQL实例.
     * @throws
     */
    public final function delete($con = null) {
        if ($con === null) {
            $sql = new DeleteSQL();
            $sql->from($this->qualifiedName)->setDialect($this->dialect);

            return $sql;
        }
        $rst = false;
        if ($con && !is_array($con)) {
            $con = [$this->primaryKeys[0] => $con];
        }
        if (is_array($con) && $con) {
            $sql = new DeleteSQL();
            $sql->from($this->qualifiedName)->setDialect($this->dialect);
            $sql->where($con);
            $rst = $sql->exec();
            $this->checkSQL($sql);
        }

        return $rst;
    }

    /**
     * 回收内容，适用于软删除(将deleted置为1).
     * 所以表中需要有deleted的字段,当其值为1时表示删除.
     * 如果uid不为0,则表中还需要有update_time与update_uid字段,
     * 分别表示更新时间与更新用户.
     *
     * @param array         $con 条件.
     * @param int           $uid 如果大于0，则表中必须包括update_time(unix时间戳)和update_uid字段.
     * @param \Closure|null $cb  回调.
     *
     * @return boolean 成功true，失败false.
     * @throws
     */
    public final function recycle(array $con, $uid = 0, $cb = null) {
        if (!$con) {
            return false;
        }
        $data ['deleted'] = 1;
        if ($uid) {
            $data ['update_time'] = time();
            $data ['update_uid']  = $uid;
        }
        $rst = $this->update($data, $con);
        if ($rst && $cb instanceof \Closure) {
            $cb ($con, $this);
        }

        return $rst;
    }

    /**
     * 过滤数据.
     *
     * @param array $data 要过滤的数据.
     */
    protected function filterFields(array &$data) {
    }

    /**
     * 从关联数组中弹出$field对应的值并将其返回.
     *
     * @param array  $data
     * @param string $field
     *
     * @return mixed
     */
    protected final function popValue(array &$data, string $field) {
        $rtn = null;
        if (isset($data[ $field ])) {
            $rtn = $data[ $field ];
            unset($data[ $field ]);
        }

        return $rtn;
    }

    /**
     * 初始化Traits.
     */
    private function parseTraits() {
        $parents = class_parents($this);
        unset($parents['wulaphp\db\Table'], $parents['wulaphp\db\View']);
        $traits = class_uses($this);
        if ($parents) {
            foreach ($parents as $p) {
                $tt = class_uses($p);
                if ($tt) {
                    $traits = array_merge($traits, $tt);
                }
            }
        }
        if ($traits) {
            $traits = array_unique($traits);
            foreach ($traits as $tt) {
                $tts   = explode('\\', $tt);
                $fname = $tts[ count($tts) - 1 ];
                $func  = 'onInit' . $fname;
                if (method_exists($this, $func)) {
                    $this->$func();
                }
            }
        }
        unset($parents, $traits);
    }
}