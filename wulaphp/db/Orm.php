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
		} elseif (method_exists($this->view, $field)) {
			$con = $this->view->{$field}();
			list($query, $fk, $lk) = $con;
			if ($eager && !isset($this->ids[ $lk ])) {
				$this->prepareIds($result, $lk);
				$datas = $query->where([$fk . ' IN' => $this->ids[ $lk ]])->toArray();
				if (isset($datas[0][ $fk ])) {
					foreach ($datas as $data) {
						$idv                                = $data[ $fk ];
						$this->eagerDatas[ $field ][ $idv ] = $data;
					}
				}
				$query->close();
			} else {
				$query->where([$fk => $result[ $index ][ $lk ]]);
			}
			$this->queries[ $field ] = $con;
		} else {
			$this->results[ $index ][ $field ] = '';

			return '';
		}

		if ($con) {
			list($query, $fk, $lk, $one, $type) = $con;
			if (isset($result[ $index ][ $lk ])) {
				$idv = $result[ $index ][ $lk ];
				if ($eager) {
					if (isset($this->eagerDatas[ $field ][ $idv ])) {
						$this->results[ $index ][ $field ] = &$this->eagerDatas[ $field ][ $idv ];
					}
				} elseif ($type == 'belongsTo' && isset($this->eagerDatas[ $field ][ $idv ])) {
					$this->results[ $index ][ $field ] = &$this->eagerDatas[ $field ][ $idv ];
				} else {
					$query->updateWhereData([$fk => $idv]);
					if ($one) {
						$data = $query->get();
					} else {
						$data = $query->toArray();
					}
					$this->results[ $index ][ $field ] = $data == null ? [] : $data;
					if ($type == 'belongsTo') {
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
			list($query, $fk, $lk) = $con;
			$query->where([$fk => $result[ $index ][ $lk ]]);
			$this->queries[ $field ] = $con;
		}

		if ($con) {
			list($query, $fk, $lk) = $con;
			if (isset($result[ $index ][ $lk ])) {
				$idv = $result[ $index ][ $lk ];
				$query->updateWhereData([$fk => $idv]);

				return $query;
			}
		}

		return null;
	}

	private function prepareIds(&$results, $key) {
		if (!isset($results[0][ $key ])) {
			$this->ids[ $key ][] = 0;

			return;
		}
		foreach ($results as $r) {
			$this->ids[ $key ][ $r[ $key ] ] = $r[ $key ];
		}
	}
}