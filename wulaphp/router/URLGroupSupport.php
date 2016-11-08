<?php

namespace wulaphp\router;
/**
 * URL分组特性.
 *
 * @package wulaphp\router
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 */
trait URLGroupSupport {
	/**
	 * Controller所在的url分组.
	 *
	 * @return array 分组定义:
	 *         0 - '~', '!', '@', '#', '%', '^', '&', '*'中的一个.
	 *         1 - group string. 字母，数字，下划线的组合.
	 */
	public abstract static function urlGroup();
}