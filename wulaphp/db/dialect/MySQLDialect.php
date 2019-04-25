<?php

namespace wulaphp\db\dialect;

use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;
use wulaphp\db\sql\ImmutableValue;
use wulaphp\db\sql\Query;

/**
 * MySQL Dialect.
 *
 * @author NingGuangfeng
 *
 */
class MySQLDialect extends DatabaseDialect {
    /**
     * @param array      $fields
     * @param array      $from
     * @param array      $joins
     * @param Condition  $where
     * @param array      $having
     * @param array      $group
     * @param array      $order
     * @param array      $limit
     * @param BindValues $values
     * @param bool       $forupdate
     *
     * @return string
     */
    public function getSelectSQL($fields, $from, $joins, $where, $having, $group, $order, $limit, $values, $forupdate) {
        $sql = ['SELECT', $fields, 'FROM'];
        $this->generateSQL($sql, $from, $joins, $where, $having, $group, $values);
        if ($order) {
            $_orders = [];
            foreach ($order as $o) {
                $_orders [] = Condition::cleanField($o [0]) . ' ' . ($o [1] == 'a' ? 'ASC' : 'DESC');
            }
            $sql [] = 'ORDER BY ' . implode(' , ', $_orders);
        }
        if ($limit) {
            $limit1 = $values->addValue('limit', $limit [0]);
            $limit2 = $values->addValue('limit', $limit [1]);
            $sql [] = 'LIMIT ' . $limit1 . ',' . $limit2;
        }
        if ($forupdate) {
            $sql[] = 'FOR UPDATE';
        }
        $sql = implode(' ', $sql);

        return $sql;
    }

    /**
     * @param                                $fields
     * @param array                          $from
     * @param array                          $joins
     * @param Condition                      $where
     * @param array                          $having
     * @param array                          $group
     * @param BindValues                     $values
     *
     * @return string
     */
    public function getCountSelectSQL($fields, $from, $joins, $where, $having, $group, $values) {
        $sql = ['SELECT', $fields, 'FROM'];
        $this->generateSQL($sql, $from, $joins, $where, $having, $group, $values);
        $sql = implode(' ', $sql);

        return $sql;
    }

    /**
     * @param string     $into
     * @param array      $data
     * @param BindValues $values
     *
     * @return string
     */
    public function getInsertSQL($into, $data, $values) {
        $sql    = "INSERT INTO $into (";
        $fields = $_values = [];
        foreach ($data as $field => $value) {
            $fields [] = Condition::cleanField($field);
            if ($value instanceof ImmutableValue) { // a immutable value
                $value->setDialect($this);
                $_values [] = $this->sanitize($value->__toString());
            } else if ($value instanceof Query) { // a sub-select SQL as a value
                $value->setBindValues($values);
                $value->setDialect($this);
                $_values [] = '(' . $value->__toString() . ')';
            } else {
                $_values [] = $values->addValue($field, $value);
            }
        }
        $sql .= implode(',', $fields) . ') VALUES (' . implode(' , ', $_values) . ')';

        return $sql;
    }

    /**
     * @param array|string $from
     * @param array        $joins
     * @param Condition    $where
     * @param BindValues   $values
     * @param array        $order
     * @param array        $limit
     *
     * @return string
     */
    public function getDeleteSQL($from, $joins, $where, $values, $order, $limit) {
        $len = count($joins);
        if ($len == 0) {
            $sql [] = 'DELETE FROM ' . $from [0][0];
        } else {
            $sql [] = 'DELETE ' . $from[0][1] . ' FROM ' . implode(' AS ', $from [0]);
            foreach ($joins as $join) {
                $sql [] = $join [2] . ' ' . $join [0] . ' AS ' . $join [3] . ' ON (' . $join [1] . ')';
            }
        }

        if ($where && count($where) > 0) {
            $sql [] = 'WHERE';
            $sql [] = $where->getWhereCondition($this, $values);
        }
        if ($len == 0) {
            if ($order) {
                $_orders = [];
                foreach ($order as $o) {
                    $_orders [] = Condition::cleanField($o [0]) . ' ' . ($o [1] == 'a' ? 'ASC' : 'DESC');
                }
                $sql [] = 'ORDER BY ' . implode(' , ', $_orders);
            }
            if ($limit) {
                $limit2 = $values->addValue('limit', $limit [1]);
                $sql [] = 'LIMIT ' . $limit2;
            }
        }

        return implode(' ', $sql);
    }

    /**
     * @param array      $table
     * @param array      $data
     * @param Condition  $where
     * @param BindValues $values
     * @param array      $order
     * @param array      $limit
     *
     * @return string
     */
    public function getUpdateSQL($table, $data, $where, $values, $order, $limit) {
        $len = count($table);
        if ($len == 1) {
            $sql = ['UPDATE', implode(' AS ', $table[0]), 'SET'];
        } else {
            $tables = [];
            foreach ($table as $t) {
                $tables[] = implode(' AS ', $t);
            }
            $sql = ['UPDATE', implode(' , ', $tables), 'SET'];
        }

        $fields = [];
        foreach ($data as $field => $value) {
            $field = Condition::cleanField($field);
            if ($value instanceof Query) {
                $value->setBindValues($values);
                $value->setDialect($this);
                $fields [] = $this->sanitize($field) . ' =  (' . $value->__toString() . ')';
            } else if ($value instanceof ImmutableValue) {
                $value->setDialect($this);
                $fields [] = $this->sanitize($field) . ' =  ' . $this->sanitize($value->__toString());
            } else {
                $fields [] = $this->sanitize($field) . ' = ' . $values->addValue($field, $value);
            }
        }

        $sql [] = implode(' , ', $fields);
        if ($where && count($where) > 0) {
            $sql [] = 'WHERE';
            $sql [] = $where->getWhereCondition($this, $values);
        }

        if ($len == 1) {
            if ($order) {
                $_orders = [];
                foreach ($order as $o) {
                    $_orders [] = Condition::cleanField($o [0]) . ' ' . ($o [1] == 'a' ? 'ASC' : 'DESC');
                }
                $sql [] = 'ORDER BY ' . implode(' , ', $_orders);
            }
            if ($limit) {
                $limit2 = $values->addValue('limit', $limit [1]);
                $sql [] = 'LIMIT ' . $limit2;
            }
        }

        return implode(' ', $sql);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareConstructOption($options) {
        if ($options instanceof DatabaseConfiguration) {
            $options = $options->toArray();
        }
        $opts    = array_merge([
            'encoding'       => 'UTF8',
            'dbname'         => '',
            'host'           => 'localhost',
            'port'           => 3306,
            'user'           => 'root',
            'password'       => 'root',
            'driver_options' => [
                \PDO::MYSQL_ATTR_COMPRESS => true
            ]
        ], $options);
        $charset = isset ($opts ['encoding']) && !empty ($opts ['encoding']) ? $opts ['encoding'] : 'UTF8';
        if ($charset == null) {
            $dsn = "mysql:dbname={$opts['dbname']};host={$opts['host']};port={$opts['port']}";
        } else {
            $dsn = "mysql:dbname={$opts['dbname']};host={$opts['host']};port={$opts['port']};charset={$charset}";
        }
        $this->charset = $charset;

        return [$dsn, $opts ['user'], $opts ['password'], $opts ['driver_options']];
    }

    /**
     * @param string $database
     * @param string $charset
     * @param array  $options
     *
     * @return bool
     */
    public function createDatabase($database, $charset = '', $options = []) {
        $sql = "CREATE DATABASE IF NOT EXISTS `{$database}`";
        if ($charset) {
            $sql .= ' DEFAULT CHARACTER SET ' . $charset;
        } else if ($this->charset) {
            $sql .= ' DEFAULT CHARACTER SET ' . $this->charset;
        } else {
            $sql .= ' DEFAULT CHARACTER SET UTF8MB4';
        }
        try {
            $rst = $this->exec($sql);

            return $rst > 0;
        } catch (\PDOException $e) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage();

            return false;
        }
    }

    /**
     * @return array
     */
    public function listDatabases() {
        $dbs = [];
        $rst = @$this->query('SHOW DATABASES');
        if ($rst) {
            $db = $rst->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($db as $d) {
                $dbs [] = $d ['Database'];
            }
        }

        return $dbs;
    }

    public function getDriverName() {
        return 'mysql';
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function sanitize($string) {
        return $string;
    }

    /**
     * generate the common SQL for select and select count
     *
     * @param array      $sql
     * @param array      $from
     * @param array      $joins
     * @param Condition  $where
     * @param array      $having
     * @param array      $group
     * @param BindValues $values
     */
    private function generateSQL(&$sql, $from, $joins, $where, $having, $group, $values) {
        $froms = [];
        foreach ($from as $f) {
            $froms [] = $f [0] . ' AS ' . $f [1];
        }
        $sql [] = implode(',', $froms);
        if ($joins) {
            foreach ($joins as $join) {
                $sql [] = $join [2] . ' ' . $join [0] . ' AS ' . $join [3] . ' ON (' . $join [1] . ')';
            }
        }
        if ($where && count($where) > 0) {
            $sql [] = 'WHERE ' . $where->getWhereCondition($this, $values);
        }
        if ($group) {
            $sql [] = 'GROUP BY ' . implode(' , ', $group);
        }
        if ($having) {
            $sql [] = 'HAVING ' . implode(' AND ', $having);
        }
    }

    /**
     * @param array                      $conditions
     * @param \wulaphp\db\sql\BindValues $values
     *
     * @return string
     */
    public function buildWhereString($conditions, $values) {
        $cons    = [];
        $dialect = $this;
        foreach ($conditions as $con) {
            list ($filed, $value) = $con;
            if (strpos($filed, '||') === 0) {
                $cons [] = 'OR';
                $filed   = substr($filed, 2);
            } else {
                $cons [] = 'AND';
            }
            $filed = trim($filed);
            if ($filed == '@' || $filed == '!@') { // exist or not exist
                $vls   = is_array($value) ? $value : [$value];
                $consx = [];
                foreach ($vls as $value) {
                    if ($value instanceof Query) {
                        $value->setBindValues($values);
                        $value->setDialect($dialect);
                        $consx [] = str_replace(['!', '@'], [
                                'NOT ',
                                'EXISTS'
                            ], $filed) . ' (' . $value->__toString() . ')';
                    }
                }
                if (empty ($consx)) {
                    array_shift($cons);
                } else {
                    $cons [] = implode(' AND ', $consx);
                }
            } else if (empty ($filed) || is_numeric($filed)) { // the value must be a Condition instance.
                if ($value instanceof Condition) {
                    $cons [] = '(' . $value->getWhereCondition($dialect, $values) . ')';
                } else if (is_array($value)) {
                    $value   = new Condition ($value);
                    $cons [] = '(' . $value->getWhereCondition($dialect, $values) . ')';
                } else if ($value instanceof ImmutableValue) {
                    $value->setDialect($this);
                    $cons [] = $this->sanitize($value->__toString());
                } else {
                    array_shift($cons);
                }
            } else { // others
                $ops = preg_split('#\s+#', $filed);
                if (count($ops) == 1) {
                    $filed = $ops [0];
                    $op    = '=';
                } else {
                    $op    = array_pop($ops);
                    $filed = implode(' ', $ops);
                }
                $op    = strtoupper($op);
                $filed = Condition::cleanField($filed);
                if ($op == '$') { // null or not null
                    if (is_null($value)) {
                        $cons [] = $filed . ' IS NULL';
                    } else {
                        $cons [] = $filed . ' IS NOT NULL';
                    }
                } else if ($op == 'BETWEEN' || $op == '#' || $op == '!#' || $op == '!BETWEEN') { // between
                    $op      = str_replace(['!', '#'], ['NOT ', 'BETWEEN'], $op);
                    $val1    = $values->addValue($filed, $value [0]);
                    $val2    = $values->addValue($filed, $value [1]);
                    $cons [] = $filed . ' ' . $op . ' ' . $val1 . ' AND ' . $val2;
                } else if ($op == 'IN' || $op == '!IN' || $op == '@' || $op == '!@') { // in
                    $op = str_replace(['!', '@'], ['NOT ', 'IN'], $op);
                    if ($value instanceof Query) { // sub-select as 'IN' or 'NOT IN' values.
                        $value->setBindValues($values);
                        $value->setDialect($dialect);
                        $cons [] = $filed . ' ' . $op . ' (' . $value->__toString() . ')';
                    } else if (is_array($value)) {
                        $vs = [];
                        foreach ($value as $v) {
                            $vs [] = $values->addValue($filed, $v);
                        }
                        $cons [] = $filed . ' ' . $op . ' (' . implode(',', $vs) . ')';
                    } else if ($value instanceof ImmutableValue) {
                        $value->setDialect($dialect);
                        $cons [] = $filed . ' ' . $op . ' (' . $dialect->sanitize($value->__toString()) . ')';
                    } else {
                        array_shift($cons);
                    }
                } else if ($op == 'LIKE' || $op == '!LIKE' || $op == '%' || $op == '!%') { // like
                    $op      = str_replace(['!', '%'], ['NOT ', 'LIKE'], $op);
                    $cons [] = $filed . ' ' . $op . ' ' . $values->addValue($filed, $value);
                } else if ($op == 'MATCH' || $op == ' *') {
                    $cons [] = "MATCH({$filed}) AGAINST (" . $values->addValue($filed, $value) . ')';
                } else if ($op == '~' || $op == '!~') {
                    $op      = str_replace(['!', '~'], ['NOT ', 'REGEXP'], $op);
                    $cons [] = $filed . ' ' . $op . ' ' . $values->addValue($filed, $value);
                } else {
                    if ($value instanceof ImmutableValue) {
                        $value->setDialect($dialect);
                        $val = $dialect->sanitize($value->__toString());
                    } else if ($value instanceof Query) {
                        $value->setBindValues($values);
                        $value->setDialect($dialect);
                        $val = '(' . $value->__toString() . ')';
                    } else {
                        $val = $values->addValue($filed, $value);
                    }
                    $cons [] = $filed . ' ' . $op . ' ' . $val;
                }
            }
        }
        if ($cons) {
            array_shift($cons);

            return implode(' ', $cons);
        }

        return '';
    }

    /**
     * 编码
     * @return string
     */
    public function getCharset() {
        return $this->charset;
    }
}