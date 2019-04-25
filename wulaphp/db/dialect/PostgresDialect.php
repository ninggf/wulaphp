<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db\dialect;

use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\sql\BindValues;
use wulaphp\db\sql\Condition;
use wulaphp\db\sql\ImmutableValue;
use wulaphp\db\sql\Query;

class PostgresDialect extends DatabaseDialect {
    private $user;

    public function createDatabase($database, $charset = '', $options = []) {
        if ($charset) {
            $set = $charset;
        } else if ($this->charset) {
            $set = $this->charset;
        } else {
            $set = 'UTF8';
        }
        $owner = $this->user;
        if (isset($options['tablespace']) && $options['tablespace']) {
            $tablespace = $options['tablespace'];
        } else {
            $tablespace = 'pg_default';
        }
        if (isset($options['con_limit']) && $options['con_limit']) {
            $con_limit = $options['con_limit'];
        } else {
            $con_limit = '-1';
        }
        $sql = <<<SQL
CREATE DATABASE "{$database}"
    WITH 
    OWNER = $owner
    ENCODING = '{$set}'
    TABLESPACE = $tablespace
    CONNECTION LIMIT = $con_limit
SQL;
        try {
            $this->exec($sql);

            return true;
        } catch (\PDOException $e) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage();

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLimit($sql, $start, $limit) {
        if (!preg_match('/\s+LIMIT\s+(%[ds]|[1-9]\d*)(\s+OFFSET\s+(%[ds]|0|[-9]\d*))?\s*$/i', $sql)) {
            return implode(' ', [' LIMIT', intval($limit), 'OFFSET', intval($start)]);
        }

        return '';
    }

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
                $_orders [] = $this->sanitize(Condition::cleanField($o [0])) . ' ' . ($o [1] == 'a' ? 'ASC' : 'DESC');
            }
            $sql [] = 'ORDER BY ' . implode(' , ', $_orders);
        }
        if ($limit) {
            $limit1 = $values->addValue('limit', $limit [0]);
            $limit2 = $values->addValue('limit', $limit [1]);
            $sql [] = 'LIMIT ' . $limit2 . ' OFFSET ' . $limit1;
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

    public function getInsertSQL($into, $data, $values) {
        $sql    = "INSERT INTO $into (";
        $fields = $_values = [];
        foreach ($data as $field => $value) {
            $fields [] = $this->sanitize(Condition::cleanField($field));
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
        $len    = count($joins);
        $jw     = [];
        $sql [] = 'DELETE FROM ' . implode(' AS ', $from [0]);
        if ($len) {
            $sql [] = 'USING';

            foreach ($joins as $join) {
                $jw[]   = $join[1];
                $sql [] = $join [0] . ' AS ' . $join [3];
            }
        }

        if ($where && count($where) > 0) {
            $sql [] = 'WHERE';
            $sql [] = $where->getWhereCondition($this, $values);
        }
        if ($jw) {
            if ($where && count($where) > 0) {
                $sql[] = 'AND ' . implode(' AND ', $jw);
            } else {
                $sql[] = 'WHERE ' . implode(' AND ', $jw);
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
        $tables = [];
        $len    = count($table);
        $sql    = ['UPDATE', implode(' AS ', $table[0]), 'SET'];
        if ($len > 1) {
            unset($table[0]);
            foreach ($table as $t) {
                $tables[] = implode(' AS ', $t);
            }
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

        if ($tables) {
            $sql[] = 'FROM ' . implode(',', $tables);
        }

        if ($where && count($where) > 0) {
            $sql [] = 'WHERE';
            $sql [] = $where->getWhereCondition($this, $values);
        }

        return implode(' ', $sql);
    }

    public function listDatabases() {
        $sql = 'SELECT datname FROM pg_database WHERE datistemplate = false';
        $dbs = [];
        $rst = $this->query($sql);
        if ($rst) {
            $db = $rst->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($db as $d) {
                $dbs [] = $d ['datname'];
            }
        }

        return $dbs;
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
                $filed = $this->sanitize(Condition::cleanField($filed));
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
                } else if ($op == '~' || $op == '!~' || $op == '~*' || $op == '!~*') {
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

    protected function prepareConstructOption($options) {
        if ($options instanceof DatabaseConfiguration) {
            $options = $options->toArray();
        }
        $opts          = array_merge([
            'encoding'       => 'UTF8',
            'dbname'         => 'postgres',
            'host'           => 'localhost',
            'port'           => 5432,
            'user'           => 'postgres',
            'password'       => 'postgres',
            'driver_options' => []
        ], $options);
        $charset       = isset ($opts ['encoding']) && !empty ($opts ['encoding']) ? $opts ['encoding'] : 'UTF8';
        $dsn           = "pgsql:dbname={$opts['dbname']};host={$opts['host']};port={$opts['port']}";
        $this->charset = $charset;
        $this->user    = $opts['user'];

        return [$dsn, $opts ['user'], $opts ['password'], $opts ['driver_options']];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function sanitize($string) {
        return str_replace('`', '', $string);
    }

    public function getDriverName() {
        return 'postgres';
    }

    public function getCharset() {
        return $this->charset;
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
}