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
 */
trait CudTrait {
	/**
	 * 上次执行是否成功.
	 *
	 * @return bool
	 */
	public function success(): bool {

		return empty ($this->error) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function go(): bool {
		return $this->exec();
	}

	/**
	 * 执行update,insert,delete语句.
	 *
	 * @param boolean $checkNum false 不检测,null直接返回影响的数量
	 *                          是否检测影响的条数.
	 *
	 * @return boolean|int|mixed
	 * @throws \PDOException
	 */
	public function exec($checkNum = false) {
		$cnt = $this->count();
		if ($cnt === false) {
			if ($this->exception instanceof \PDOException) {
				$this->error = $this->exception->getMessage();

				return false;
			}

			return is_null($checkNum) ? 0 : false;
		} else if ($this instanceof InsertSQL) {
			if ($checkNum) {
				return $cnt > 0;
			} else if (is_null($checkNum)) {
				return $cnt;
			} else {
				$ids = $this->lastInsertIds();

				return $ids;
			}
		} else if (is_null($checkNum)) {
			return $cnt;
		} else if ($checkNum) {
			return $cnt > 0;
		} else {
			return true;
		}
	}

	/**
	 * 返回影响的行数.
	 *
	 * @return int
	 */
	public function affected(): int {
		return $this->exec(null);
	}

	/**
	 * 获取SQL语句.
	 *
	 * @return string
	 */
	public function getSqlString() {
		return $this->sql ?? '';
	}
}