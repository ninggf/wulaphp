<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\validator;
/**
 * Trait JQueryValidator
 * @package wulaphp\validator
 * @method  array getValidateRules()
 */
trait JQueryValidator {
	private $rules;

	/**
	 * 生成可供jquery.validator插件使用的验证规则.
	 *
	 * @return string
	 */
	public function encodeValidatorRule() {
		if (method_exists($this, 'getValidateRules')) {
			$this->rules = $this->getValidateRules();
		}
		$rules = [];
		$msgs  = [];
		if ($this->rules) {
			foreach ($this->rules as $name => $rs) {
				foreach ($rs as $r) {
					@list($rule, $exp, $msg) = $r;
					if ($exp) {
						$rules[ $name ][ $rule ] = $exp;
					} else {
						$rules[ $name ][ $rule ] = true;
					}
					$msgs[ $name ][ $rule ] = $msg;
				}
			}
		}

		$rtn = ['rules' => $rules, 'messages' => $msgs];

		return json_encode($rtn);
	}
}