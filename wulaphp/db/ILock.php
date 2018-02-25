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

interface ILock {
	/**
	 * 锁定.
	 *
	 * @return bool|array|mixed 锁定失败返回false.否则返回其它值，特别注意，空数组也可能是锁定成功哦.
	 */
	public function lock();
}