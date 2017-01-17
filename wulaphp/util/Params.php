<?php

namespace wulaphp\util;

use wulaphp\validator\ValidateException;

/**
 * 参数定义类，用于快速给方法提供准确参数.
 * 调用方法:
 * <code>
 *  $param = $params->getParams($error);
 * </code>
 *
 * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
 * 如果想输出null,请使用imv('null')对参数进行赋值。
 * 只需定义public属性即可。
 * @method onInitValidator($fields = [])
 * @method validateNewData(array $data)
 */
abstract class Params {
	private $__vars = [];

	public function __construct() {
		$obj  = new ReflectionObject($this);
		$vars = $obj->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($vars as $var) {
			$name = $var->getName();
			if ($paramsFields === false) {
				$ann             = new Annotation($var);
				$fields[ $name ] = ['annotation' => $ann];
			}
		}
		if ($fields && method_exists($this, 'onInitValidator')) {
			$this->onInitValidator($fields);
		}
		$this->__vars = $fields;
	}

	/**
	 * 获取参数列表.
	 * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
	 * 如果想输出null,请使用imv('null')对参数进行赋值。
	 *
	 * @param array $errors 错误信息，如果启用了验证功能且验证出错时的错误信息.
	 *
	 * @return array 参数数组
	 */
	public function getParams(&$errors = null) {
		$ary    = [];
		$fields = $this->__vars;
		foreach ($fields as $field => $v) {
			$value = $this->{$field};
			if (is_null($value)) {
				continue;
			}
			$ary[ $field ] = $value;
		}
		unset($obj, $vars, $var);
		if ($fields && method_exists($this, 'onInitValidator')) {
			try {
				$this->validateNewData($ary);
			} catch (ValidateException $e) {
				$errors = $e->getErrors();

				return null;
			}
		}

		return $ary;
	}
}