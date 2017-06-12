<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\wulaphp\auth;

use wulaphp\auth\Passport;

abstract class AclExtraChecker {
	/**
	 * @var \wulaphp\wulaphp\auth\AclExtraChecker
	 */
	private $next;

	/**
	 * 添加下一个检验器.
	 *
	 * @param \wulaphp\wulaphp\auth\AclExtraChecker $checker
	 */
	public final function next(AclExtraChecker $checker) {
		$this->next = $checker;
	}

	/**
	 * 权限检验.
	 *
	 * @param Passport $passport
	 * @param string   $op
	 * @param array    $extra
	 *
	 * @return bool
	 */
	public final function check(Passport $passport, $op, $extra) {
		$rst = $this->doCheck($passport, $op, $extra);
		if ($rst && $this->next) {
			$rst = $this->next->check($passport, $op, $extra);
		}

		return $rst;
	}

	/**
	 * 校验.
	 *
	 * @param Passport $passport 通行证
	 * @param string   $op       操作
	 * @param array    $extra    额外数据
	 *
	 * @return mixed
	 */
	protected abstract function doCheck(Passport $passport, $op, $extra);
}