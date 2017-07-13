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

use wulaphp\app\App;
use wulaphp\mvc\controller\Controller;

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
	 * @param Controller $controller
	 *
	 * @return string
	 */
	public function encodeValidatorRule(Controller $controller = null) {
		if (method_exists($this, 'getValidateRules')) {
			$this->rules = $this->getValidateRules();
		}
		$url = '';
		if ($controller) {
			$clsName = get_class($controller);
			$url     = App::action($clsName . '::validate') . '/' . str_replace('\\', '.', get_class($this));
		}
		$rules = [];
		$msgs  = [];
		if ($this->rules) {
			foreach ($this->rules as $name => $rs) {
				foreach ($rs as $r) {
					@list($rule, $exp, $msg, $m) = $r;
					$key = $name . ($m ? '[]' : '');
					if ($rule == 'callback') {
						if (!$url) {
							continue;
						}
						$rule = 'remote';
						$exp  = [
							'url' => $url . '/' . $name,
							'rqs' => explode(',', trim(preg_replace('/.+?(\((.*)\))?$/', '\2', $exp)))
						];
					} else if ($rule == 'rangelength' || $rule == 'range' || $rule == 'rangeWords') {
						$exp = explode(',', $exp);
					}
					if ($exp) {
						$rules[ $key ][ $rule ] = $exp;
					} else {
						$rules[ $key ][ $rule ] = true;
					}
					if (!$msg) {
						$msg = _tt($rule . '@validator');
					}
					if (strpos($msg, '%s') !== false) {
						$i   = 0;
						$msg = preg_replace_callback('/%s/', function ($ms) use (&$i) {
							return '{' . ($i++) . '}';
						}, $msg);
					}
					$msgs[ $key ][ $rule ] = $msg;
				}
			}
		}

		$rtn = ['rules' => $rules, 'messages' => $msgs];

		return json_encode($rtn);
	}
}