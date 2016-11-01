<?php

namespace wulaphp\util;
/**
 * 参数定义类，用于快速给方法提供准确参数.
 * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
 * 如果想输出null,请使用imv('null')对参数进行赋值。
 * 只需定义public属性即可。
 */
abstract class Params {
	/**
	 * 获取参数列表.
	 * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
	 * 如果想输出null,请使用imv('null')对参数进行赋值。
	 * @return array 参数数组.
	 */
	public function toArray() {
		$obj  = new \ReflectionObject($this);
		$vars = $obj->getProperties(\ReflectionProperty::IS_PUBLIC);
		$ary  = [];
		foreach ($vars as $var) {
			$name  = $var->getName();
			$value = $var->getValue($obj);

			if (is_null($value)) {
				continue;
			}
			$ary[ $name ] = $value;
		}
		unset($obj, $vars, $var);

		return $ary;
	}
}