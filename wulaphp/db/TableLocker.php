<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\wulaphp\db;

use wulaphp\db\sql\Query;

class TableLocker implements ILock {
	/**
	 * @var Query
	 */
	protected $query;

	public function __construct(Query $query) {
		$this->query;
	}

	/**
	 *
	 * @return array|bool
	 */
	public function lock() {
		return $this->query->forupdate();
	}
}