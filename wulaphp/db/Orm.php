<?php

namespace wulaphp\db;

use wulaphp\db\sql\Query;

/**
 * ORM解析类.
 *
 * @package wulaphp\db
 */
class Orm {
	private $view;
	private $results    = [];
	private $queries    = [];
	private $primaryKey;
	private $ids        = null;
	private $eagerDatas = [];

	public function __construct(View $view, $pk) {
		$this->view       = $view;
		$this->primaryKey = $pk;
	}

	public function __destruct() {
		if ($this->queries) {
			/**@var Query $a [0] */
			foreach ($this->queries as $a) {
				$a[0]->close();
			}
		}
		unset($this->results, $this->queries);
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
	public function getData($index, $field, &$result, $eager = false) {
		if (isset($this->results[ $index ][ $field ])) {
			return $this->results[ $index ][ $field ];
		}

		$this->results[ $index ][ $field ] = [];
		$con                               = null;
		if (isset($this->queries[ $field ])) {
			$con = $this->queries[ $field ];
		} else if (method_exists($this->view, $field)) {
			//构建查询
			$con = $this->view->{$field}();
			/**@var Query $query */
			list($query, $fk, $lk, , $type) = $con;
			// 预加载数据，只有belongsTo类型的关系才能预加载.
			if ($eager && $type == 'belongsTo' && !isset($this->ids[ $field ])) {
				$this->prepareIds($result, $lk, $field);
				$datas                      = $query->where([$fk . ' IN' => $this->ids[ $field ]])->toArray(null, $fk);
				$this->eagerDatas[ $field ] = $datas;
				$query->close();
			} else {
				$query->where([$fk => $result[ $index ][ $lk ]]);
			}
			$this->queries[ $field ] = $con;
		} else {
			return [];
		}

		if ($con) {
			/**@var Query $query */
			list($query, $fk, $lk, $one, $type) = $con;
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
	public function getQuery($index, $field, &$result) {
		$con = null;
		if (isset($this->queries[ $field ])) {
			$con = $this->queries[ $field ];
		} elseif (method_exists($this->view, $field)) {
			$con = $this->view->{$field}();
			/**@var Query $query */
			list($query, $fk, $lk) = $con;
			$query->where([$fk => $result[ $index ][ $lk ]]);
			$this->queries[ $field ] = $con;
		}

		if ($con) {
			/**@var Query $query */
			list($query, $fk, $lk) = $con;
			if (isset($result[ $index ][ $lk ])) {
				$idv = $result[ $index ][ $lk ];
				$query->updateWhereData([$fk => $idv]);

				return $query;
			}
		}

		return null;
	}

	private function prepareIds(&$results, $key, $field) {
		if (!isset($results[0][ $key ])) {
			$this->ids[ $field ][] = 0;

			return;
		}
		foreach ($results as $r) {
			$this->ids[ $field ][ $r[ $key ] ] = $r[ $key ];
		}
	}
}