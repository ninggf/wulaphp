<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db\sql;
/**
 * Trait CudTrait
 * @package wulaphp\db\sql
 * @property-read \wulaphp\db\dialect\DatabaseDialect $dialect
 */
trait CudTrait {
    protected $retriedCnt = 0;
    /**
     * 上次执行是否成功.
     *
     * @return bool
     */
    public function success(): bool {
        return empty ($this->error) ? true : false;
    }

    /**
     * 执行update,insert,delete语句.
     *
     * @return bool 影响的行数大于0时返回true,反之返回false；
     * @throws
     */
    public function go(): bool {
        return $this->exec(true);
    }

    /**
     * 执行update,insert,delete语句.
     *
     * @return bool 执行成功返回true，失败返回false。
     */
    public function execute(): bool {
        return $this->exec(false);
    }

    /**
     * 执行update,insert,delete语句.
     *
     * @return int 返回影响的行数.
     */
    public function affected(): int {
        return $this->exec(null);
    }

    /**
     * 执行update,insert,delete语句.
     *
     * @param boolean|null $checkAffected 是否检测影响的条数 .
     *                                1. false不检测影响的行数，SQL语句执行成功返回true，反之false；
     *                                1.1 如果是insert语句执行成功则返回auto_increment ID.
     *                                2. true影响的行数大于0时返回true,反之返回false；
     *                                3. null直接返回影响的行数；
     *
     *
     * @return boolean|int|mixed
     *
     * @throws \PDOException
     */
    public function exec(?bool $checkAffected = false) {
        $cnt = $this->count();
        if ($cnt === false) {//SQL执行失败
            if ($this->exception instanceof \PDOException) {
                $this->error = $this->exception->getMessage();
            }
            $dn = $this->dialect->getDriverName();
            log_error($this->error . '[' . $this->getSqlString() . ']', $dn . '.sql.err');

            return is_null($checkAffected) ? 0 : false;
        } else if ($this instanceof InsertSQL) {
            if ($checkAffected) {
                return $cnt > 0;
            } else if (is_null($checkAffected)) {
                return $cnt;
            } else if ($this->keyField) {
                return $this->lastInsertIds();
            } else {
                return true;
            }
        } else if (is_null($checkAffected)) {
            return $cnt;
        } else if ($checkAffected) {
            return $cnt > 0;
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        return strval($this->getSQL());
    }

    /**
     * 获取SQL语句.
     *
     * @return string
     */
    public function getSqlString() {
        $sql = $this->getSQL();
        if ($sql && $this->values) {
            foreach ($this->values as $value) {
                [$name, $val, $type, , $rkey] = $value;
                if ($this->whereData) {
                    $val = isset($this->whereData[ $rkey ]) ? $this->whereData[ $rkey ] : (isset($this->whereData[ $name ]) ? $this->whereData[ $name ] : $val);
                }
                if ($type == \PDO::PARAM_STR) {
                    $sql = str_replace($name, $this->dialect->quote($val), $sql);
                } else {
                    $sql = str_replace($name, $val, $sql);
                }
            }
        }

        return $sql;
    }

    /**
     * @return string|null|bool
     */
    protected abstract function getSQL();
}