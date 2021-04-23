<?php

namespace wulaphp\db;

use wulaphp\db\sql\Query;

/**
 * ORM解析类.
 *
 * @package wulaphp\db
 * @internal
 */
class Orm {
    private        $view;
    private        $results    = [];
    private        $queries    = [];
    private        $primaryKey;
    private        $ids        = null;
    private        $eagerDatas = [];
    private static $cnt        = 0;

    public function __construct(View $view, $pk) {
        $this->view       = $view;
        $this->primaryKey = $pk;
        self::$cnt ++;
    }

    public function __destruct() {
        if ($this->queries) {
            /**@var Query $a [0] */
            foreach ($this->queries as $a) {
                $a[0]->close();
            }
            unset($this->queries);
        }
        unset($this->results, $this->eagerDatas, $this->ids);
    }

    /**
     *
     * @return \wulaphp\db\Orm
     */
    public function cloneit(): Orm {
        return new Orm($this->view, $this->primaryKey);
    }

    /**
     * 取数据.
     *
     * @param int    $index
     * @param string $field
     * @param array  $result
     * @param bool   $eager 是否预加载
     *
     * @return mixed
     */
    public function getData(int $index, string $field, array &$result, bool $eager = false) {
        if (isset($this->results[ $index ][ $field ])) {
            return $this->results[ $index ][ $field ];
        }

        $this->results[ $index ][ $field ] = [];
        if (isset($this->queries[ $field ])) {
            $con = $this->queries[ $field ];
        } else {
            try {
                //构建查询
                $con = $this->view->{$field}();
                /**@var Query $query */
                [$query, $fk, $lk, , $type] = $con;
                // 预加载数据，只有belongsTo和hasOne类型的关系才能预加载.
                if ($eager && ($type == 'belongsTo' || $type == 'hasOne') && !isset($this->ids[ $field ])) {
                    $this->prepareIds($result, $lk, $field);
                    $datas                      = $query->where([$fk . ' IN' => $this->ids[ $field ]])->toArray(null, $fk);
                    $this->eagerDatas[ $field ] = $datas;
                    $query->close();
                } else {
                    $query->where([$fk => $result[ $index ][ $lk ]]);
                }
                $this->queries[ $field ] = $con;
            } catch (\Exception $e) {
                return [];
            }
        }

        if ($con) {
            /**@var Query $query */
            [$query, $fk, $lk, $one, $type] = $con;
            if (isset($result[ $index ][ $lk ])) {
                $idv = $result[ $index ][ $lk ];
                if (isset($this->eagerDatas[ $field ][ $idv ])) {
                    $this->results[ $index ][ $field ] = &$this->eagerDatas[ $field ][ $idv ];
                } else {
                    $query->updateWhereData([$fk => $idv]);
                    if ($one) {
                        $data = $query->get(0);
                    } else {
                        $data = $query->toArray();
                    }
                    $this->results[ $index ][ $field ] = $data == null ? [] : $data;
                    if (strpos($type, 'belongs') === 0) {
                        $this->eagerDatas[ $field ][ $idv ] = &$this->results[ $index ][ $field ];
                    }
                }
            }
        }

        return $this->results[ $index ][ $field ];
    }

    /**
     * 取数据.
     *
     * @param int    $index
     * @param string $field
     * @param array  $result
     *
     * @return Query
     */
    public function getQuery(int $index, string $field, array &$result): ?Query {
        $con = null;
        if (isset($this->queries[ $field ])) {
            $con = $this->queries[ $field ];
        } else if (method_exists($this->view, $field)) {
            $con = $this->view->{$field}();
            /**@var Query $query */
            [$query, $fk, $lk] = $con;
            $query->where([$fk => $result[ $index ][ $lk ]]);
            $this->queries[ $field ] = $con;
        }

        if ($con) {
            /**@var Query $query */
            [$query, $fk, $lk] = $con;
            if (isset($result[ $index ][ $lk ])) {
                $idv = $result[ $index ][ $lk ];
                $query->updateWhereData([$fk => $idv]);

                return $query;
            }
        }

        return null;
    }

    /**
     * for with option.
     *
     * @param array  $results
     * @param string $key
     * @param string $field
     */
    private function prepareIds(array &$results, string $key, string $field) {
        if (!isset($results[0][ $key ])) {
            $this->ids[ $field ][] = 0;

            return;
        }
        foreach ($results as $r) {
            $this->ids[ $field ][ $r[ $key ] ] = $r[ $key ];
        }
    }
}