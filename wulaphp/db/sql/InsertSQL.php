<?php

namespace wulaphp\db\sql;

class InsertSQL extends QueryBuilder implements \ArrayAccess, \IteratorAggregate {
    use CudTrait;

    private $intoTable;
    private $datas;
    private $batch;
    private $ids      = [];
    private $idsGot   = false;
    private $executed = null;
    private $keyField = null;
    private $keySet   = false;
    private $dupData  = null;//冲突时要更新的数据.
    private $dupKey   = null;//冲突键.

    public function __construct($datas, $batch = false) {
        $this->datas = $datas;
        $this->batch = $batch;
    }

    /**
     * specify the auto increment key
     *
     * @param string $key
     *
     * @return \wulaphp\db\sql\InsertSQL
     */
    public function autoKey(string $key): InsertSQL {
        $this->keyField = $key;

        return $this;
    }

    /**
     * alias of autoKey
     *
     * @param string $field
     *
     * @return $this
     */
    public function autoField(string $field): InsertSQL {
        $this->keyField = $field;

        return $this;
    }

    /**
     * the datas will be inserted into whitch table.
     *
     * @param string $table
     *
     * @return  \wulaphp\db\sql\InsertSQL
     */
    public function into(string $table): InsertSQL {
        $this->intoTable = $table;

        return $this;
    }

    /**
     * just use count() function to perform this SQL and get the affected rows(inserted)
     *
     * @return int|false
     * @see Countable::count()
     */
    public function count() {
        if ($this->executed !== null) {
            return $this->executed;
        }
        $this->executed = false;//不为null时即为已经执行。
        $sql            = $this->getSQL();
        $values         = $this->values;
        if ($sql) {
            $statement = null;
            try {
                $ids = array_keys($this->datas);
                if ($this->batch && count($this->datas) > 1) {
                    unset($ids[0]);
                    $sqlValues = [];
                    foreach ($ids as $idx) {
                        $d       = $this->datas[ $idx ];
                        $vstring = [];
                        foreach ($d as $ff => $vv) {
                            if ($vv instanceof ImmutableValue) { // a immutable value
                                $vstring [] = $vv->getValue($this->dialect);
                            } else if ($vv instanceof Query) { // a sub-select SQL as a value
                                $vv->setBindValues($values);
                                $vv->setDialect($this->dialect);
                                $vstring [] = '(' . $vv . ')';// -> __toString()
                            } else {
                                $vstring [] = $values->addValue($ff, $vv);
                            }
                        }
                        $sqlValues[] = '(' . implode(' , ', $vstring) . ')';
                    }
                    $this->sql = $sql = $sql . ',' . implode(', ', $sqlValues);
                }
                if ($this->dupKey) {
                    $ondup = $this->dialect->getOnDuplicateSet($this->dupKey);
                    if (!$ondup) {
                        $this->errorSQL    = '';
                        $this->errorValues = null;
                        $this->error       = get_class($this->dialect) . ' cannot support upsert!';

                        return false;
                    }
                    $upkvs = [];
                    foreach ($this->dupData as $dk => $dv) {
                        if ($dv instanceof ImmutableValue) {
                            $rv = $dv->getValue($this->dialect);
                        } else {
                            $rv = $values->addValue($dk, $dv);
                        }
                        $upkvs[] = Condition::cleanField($dk, $this->dialect) . ' = ' . $rv;
                    }
                    $this->sql = $sql = $sql . ' ' . $ondup . ' ' . implode(', ', $upkvs);
                }
                //create prepare statement
                try {
                    $statement = $this->dialect->prepare($sql);
                } catch (\Exception $e) {
                    if ($this->retriedCnt == 0 && $e->getCode() == 'HY000') {
                        try {
                            $this->dialect    = $this->dialect->reset();
                            $this->executed   = null;
                            $this->retriedCnt = 1;

                            return $this->count();
                        } catch (\Exception $e1) {
                            $this->errorSQL    = $sql;
                            $this->errorValues = $values->__toString();
                            $this->error       = $e1->getMessage();

                            return false;
                        }
                    } else {
                        $this->errorSQL    = $sql;
                        $this->errorValues = $values->__toString();
                        $this->error       = $e->getMessage();

                        return false;
                    }
                }
                //bind values
                foreach ($values as $value) {
                    [$name, $val, $type] = $value;
                    if (!$statement->bindValue($name, $val, $type)) {
                        $this->errorSQL    = $sql;
                        $this->errorValues = $values->__toString();
                        $this->error       = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;

                        return false;
                    }
                }

                $rst = $statement->execute();
                if ($rst) {
                    $this->executed = $statement->rowCount();

                    return $this->executed;
                } else {
                    $this->error = 'cannot execute ' . $this->getSqlString();
                    $this->dumpSQL($statement);
                }
            } catch (\PDOException $e) {
                $this->exception   = $e;
                $this->error       = $e->getMessage();
                $this->errorSQL    = $sql;
                $this->errorValues = $values->__toString();
            } finally {
                if ($statement) {
                    $statement->closeCursor();
                    $statement = null;
                }
            }
        } else {
            $this->error       = 'Can not generate the insert SQL';
            $this->errorSQL    = '';
            $this->errorValues = $values->__toString();
        }

        return false;
    }

    /**
     * 获取 insert 语句生成的自增型ID.
     *
     * @param string|null $field 字段名.
     *
     * @return int
     */
    public function newId(?string $field = null): int {
        $ids            = [];
        $this->keyField = $field;
        $cnt            = $this->count();
        if ($cnt === false) {
            if ($this->exception instanceof \PDOException) {
                $this->error = $this->exception->getMessage();
            }
            log_error($this->error . '[' . $this->getSqlString() . ']', 'sql.err');

            return 0;
        } else if ($cnt > 0) {
            $ids = $this->lastInsertIds();
        }

        return $ids ? $ids[0] : 0;
    }

    public function offsetExists($offset) {
        return isset ($this->ids [ $offset ]);
    }

    public function offsetGet($offset) {
        return $this->ids [ $offset ];
    }

    /**
     * 获取最后生成或使用的主键.
     *
     * @return array
     */
    protected function lastInsertIds(): array {
        if (!$this->idsGot) {
            $this->idsGot = true;
            if ($this->keySet) {
                $this->fillAutoIds();
            } else if (!$this->dupKey) {
                try {
                    $this->ids [] = $this->dialect->lastInsertId($this->keyField);
                } catch (\Exception $e) {
                }
            }
        }

        return $this->ids;
    }

    public function offsetSet($offset, $value) {
    }

    public function offsetUnset($offset) {
    }

    /**
     * 获取最后生成的自增字段.
     *
     * @param string|null $field
     *
     * @return int
     */
    public function lastId(?string $field = null): int {
        try {
            $this->checkDialect();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return 0;
        }

        return $this->dialect->lastInsertId($field);
    }

    /**
     * 唯一键冲突时更新数据.
     *
     * @param string     $key 冲突键.
     * @param array|null $data
     *
     * @return $this
     */
    public function onDuplicate(string $key, ?array $data = null): InsertSQL {
        $this->dupKey  = $key;
        $this->dupData = $data;

        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator {
        return new \ArrayIterator ($this->ids);
    }

    /**
     * 获取执行后的SQL.
     *
     * @return string
     */
    public function getSqlString() {
        $sql    = $this->getSQL();
        $ids    = array_keys($this->datas);
        $values = new BindValues($this->values);
        if ($this->batch && count($this->datas) > 1) {
            unset($ids[0]);
            $sqlValues = [];
            foreach ($ids as $idx) {
                $d       = $this->datas[ $idx ];
                $vstring = [];
                foreach ($d as $ff => $vv) {
                    if ($vv instanceof ImmutableValue) { // a immutable value
                        $vstring [] = $vv->getValue($this->dialect);
                    } else if ($vv instanceof Query) { // a sub-select SQL as a value
                        $vv->setBindValues($values);
                        $vv->setDialect($this->dialect);
                        $vstring [] = '(' . $vv . ')';
                    } else {
                        $vstring [] = $values->addValue($ff, $vv);
                    }
                }
                $sqlValues[] = '(' . implode(' , ', $vstring) . ')';
            }
            $sql = $sql . ',' . implode(' , ', $sqlValues);
        }
        if ($sql && $values) {
            foreach ($values as $value) {
                [$name, $val, $type] = $value;
                if ($type == \PDO::PARAM_STR) {
                    $sql = str_replace($name, $this->dialect->quote($val), $sql);
                } else {
                    $sql = str_replace($name, $val, $sql);
                }
            }
        }

        return $sql;
    }

    protected function getSQL() {
        if ($this->sql) {
            return $this->sql;
        }
        if (empty ($this->intoTable)) {
            $this->error = 'no table specified!';

            return false;
        }
        if (empty ($this->datas)) {
            $this->error = 'no data to insert!';

            return false;
        }
        try {
            $this->checkDialect();
        } catch (\Exception $e) {
            $this->exception = $e;
            $this->error     = $e->getMessage();

            return false;
        }
        $this->values = new BindValues ();
        $ids          = array_keys($this->datas);
        $data         = $this->batch ? $this->datas [ $ids [0] ] : $this->datas;
        $into         = $this->prepareFrom([[$this->intoTable, null]]);
        $sql          = $this->dialect->getInsertSQL($into [0] [0], $data, $this->values);
        $this->sql    = $sql;
        $this->keySet = $this->keyField && isset($data[ $this->keyField ]);

        return $this->sql;
    }

    /**
     * 根据数据填充主键.
     */
    private function fillAutoIds() {
        if ($this->batch) {
            $this->ids[] = $this->datas[ count($this->datas) - 1 ][ $this->keyField ];
        } else {
            $this->ids[] = $this->datas[ $this->keyField ];
        }
    }
}