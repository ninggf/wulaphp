<?php

namespace wulaphp\router;

trait URLPrefixSupport {
	/**
	 * @return array [char,prefix]
	 */
	public static function getURLPrefix() {
		trigger_error('must be rewroten in subclass', E_USER_WARNING);

		return null;
	}
}