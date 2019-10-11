<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db;

use wulaphp\db\sql\Query;

/**
 * 基于`select forupdate`的锁。
 *
 * @package wulaphp\db
 */
class TableLocker implements ILock {
	/**
	 * @var Query
	 */
	protected $query;

	public function __construct(Query $query) {
		$this->query = $query;
	}

	/**
	 *
	 * @return array|bool
	 */
	public function lock() {
		if ($this->query) {
			return $this->query->forupdate();
		} else {
			return false;
		}
	}
}