<?php

namespace wulaphp\util;

class ObjectCaller {

	/**
	 * 调用对像的方法，最多10个参数.
	 *
	 * @param \stdClass|mixed $obj
	 * @param string          $method
	 * @param array           $args
	 *
	 * @return mixed
	 */
	public static function callObjMethod($obj, $method, $args = []) {
		return $obj->{$method} (...$args);
	}

	public static function callClzMethod($clz, $method, $args = []) {
		if (is_object($clz)) {
			$clz = get_class($clz);
		}

		return $clz::{$method}(...$args);
	}
}
