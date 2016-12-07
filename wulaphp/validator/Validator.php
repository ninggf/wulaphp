<?php

namespace wulaphp\validator;

use wulaphp\db\sql\ImmutableValue;

/**
 * 数据检验器.
 * @package wulaphp\validator
 *
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 * @property array $fields
 */
trait Validator {
	private   $rules          = [];
	private   $rulesIdx       = [];
	private   $ruleKeys       = [];
	protected $preDefinedRule = ['required' => true, 'equalTo' => true, 'notEqualTo' => true, 'notEqual' => true, 'num' => true, 'number' => true, 'digits' => true, 'min' => true, 'max' => true, 'gt' => true, 'ge' => true, 'lt' => true, 'le' => true, 'range' => true, 'minlength' => true, 'maxlength' => true, 'rangelength' => true, 'callback' => true, 'pattern' => true, 'regexp' => true, 'email' => true, 'url' => true, 'ip' => true, 'date' => true, 'datetime' => true];

	protected function onInitValidator($fields = []) {
		if (empty($fields)) {
			if (isset($this->fields) && $this->fields) {
				$fields = $this->fields;
			}
		}
		if ($fields) {
			foreach ($fields as $field => $def) {
				$rule = [];
				$ann  = $def['annotation'];
				$anns = $ann->getAll();
				foreach ($anns as $an => $va) {
					if (isset($this->preDefinedRule[ $an ])) {
						$r   = $va ? explode(' => ', $va) : [];
						$len = count($r);
						if ($len == 0) {
							$rule[ $an ] = null;
						} else {
							if (preg_match('/^\(.+?\)$/', $r[0])) {
								if (isset($r[1])) {
									$rule[ $an . $r[0] ] = $r[1];
								} else {
									$rule[ $an . $r[0] ] = null;
								}
							} else {
								$rule[ $an ] = $r[0];
							}
						}
					}
				}
				if ($rule) {
					$this->addRule($field, $rule);
				}
			}
		}
	}

	/**
	 * 数据校验规则.
	 *
	 * @param array $fileds 字段.
	 *
	 * @return array
	 */
	public function getValidateRules(...$fileds) {
		if ($fileds) {
			$rules = [];
			foreach ($fileds as $f) {
				if (isset($this->rules[ $f ])) {
					$rules[ $f ] = $this->rules[ $f ];
				}
			}

			return $rules;
		}

		return $this->rules;
	}

	/**
	 * 验证数据.
	 *
	 * @param array $data  待验证的数据.
	 * @param array $rules 验证规则.
	 *
	 * @return bool
	 * @throws ValidateException
	 */
	public function validate(array $data, array $rules) {
		$this->rules    = [];
		$this->rulesIdx = [];
		$this->ruleKeys = [];
		foreach ($rules as $field => $rule) {
			$this->addRule($field, $rule);
		}

		return $this->validateNewData($data);
	}

	/**
	 * 验证新增数据,用于insert语句.
	 *
	 * @param array $data
	 *
	 * @return bool
	 * @throws ValidateException
	 */
	protected function validateNewData(array $data) {
		if ($this->rules) {
			return $this->validateData($this->rules, $data);
		}

		return true;
	}

	/**
	 * 验证修改数据,用于update.
	 *
	 * @param array $data
	 *
	 * @return bool
	 * @throws ValidateException
	 */
	protected function validateUpdateData(array $data) {
		if ($this->rules) {
			$newRules = [];
			foreach ($data as $key => $v) {
				if (isset($this->rules[ $key ])) {
					$newRules[ $key ] = $this->rules[ $key ];
				}
			}
			if ($newRules) {
				return $this->validateData($newRules, $data);
			}
		}

		return true;
	}

	/**
	 * 添加验证规则.
	 *
	 * @param string $field
	 * @param array  $rules
	 */
	public function addRule($field, array $rules) {
		foreach ($rules as $rule => $m) {
			if (is_int($rule)) {
				$rule = $m;
				list($r, $ops) = $this->parseRule($rule);
				$m = '';
			} else {
				list($r, $ops) = $this->parseRule($rule);
			}

			if ($r) {
				$this->rulesIdx[ $field ] += 1;
				$idx                            = $this->rulesIdx[ $field ];
				$this->rules[ $field ][ $idx ]  = [$r, $ops, $m];
				$this->ruleKeys[ $field ][ $r ] = $idx;
			}
		}
	}

	/**
	 * 删除验证规则.
	 *
	 * @param string      $field
	 * @param string|null $rule
	 */
	public function removeRule($field, $rule = null) {
		if ($rule == null) {
			unset($this->rules[ $field ]);
		} else {
			$idx = $this->ruleKeys[ $field ][ $rule ];
			unset($this->rules[ $field ][ $idx ]);
		}
	}

	/**
	 * @param array $rules
	 * @param array $data
	 *
	 * @return bool
	 * @throws ValidateException
	 */
	private function validateData($rules, $data) {
		$errors = [];
		foreach ($rules as $field => $rule) {
			$valid = $this->validateField($field, $data, $rule);
			if ($valid !== true) {
				$errors[ $field ] = $valid;
			}
		}
		if (!empty($errors)) {
			throw new ValidateException($errors);
		}

		return true;
	}

	/**
	 * @param string $field
	 * @param array  $data
	 * @param array  $rules
	 *
	 * @return bool|mixed
	 */
	private function validateField($field, $data, $rules) {
		foreach ($rules as $rule) {
			list($m, $ops, $msg) = $rule;
			$valid_m = 'v_' . $m;
			if (method_exists($this, $valid_m)) {
				$valid = $this->$valid_m ($field, $ops, $data, $msg);
			} else {
				return __('notsupportmethod@validator', $field, $m);
			}
			if ($valid !== true) {
				return $valid;
			}
		}

		return true;
	}

	/**
	 * @param $rule
	 *
	 * @return array
	 */
	private function parseRule($rule) {
		$parsed = [false];
		if (preg_match('#^([a-z]+)\s*\(([^\)]*)\)\s*$#i', $rule, $ms)) {
			$method = trim($ms[1]);
			$ops    = trim($ms[2]);
		} else {
			$method = trim($rule);
			$ops    = '';
		}
		$parsed[0] = $method;
		$parsed[1] = $ops;

		return $parsed;
	}

	// 必填项目
	protected function v_required($field, $exp, $data, $message) {
		//如果$exp为空,那么需要检测此字段。
		$needCheck = true;
		if (!empty($exp)) {
			$exp       = explode(',', $exp);
			$needCheck = false;
			foreach ($exp as $e) {
				$es  = explode('&&', $e);
				$rst = true;//每一组条件默认是需要检测.
				foreach ($es as $ne) {
					$ne = trim($ne);
					//所有条件都成立时才需要检测.
					$rst = $rst && !$this->isEmpty($ne, $data);
					if ($rst) {
						continue;
					}
				}
				$needCheck = $needCheck || $rst;
				if ($needCheck) {
					break;
				}
			}
		}
		if (!$needCheck) {
			return true;
		}

		if (!isset($data[ $field ])) {
			return empty ($message) ? __('required', $field) : sprintf($message, $field);
		}

		$value = $data[ $field ];
		if ($value instanceof ImmutableValue) {
			return true;
		}

		if (!$this->isEmpty($field, $data)) {
			return true;
		} else {
			return empty ($message) ? __('required', $field) : sprintf($message, $field);
		}
	}

	// 相等
	protected function v_equalTo($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = false;
		if (isset ($data [ $exp ])) {
			$rst = $value == $data [ $exp ];
		}
		if ($rst) {
			return true;
		} else {
			return empty ($message) ? __('equalTo@validator') : sprintf($message, $value);
		}
	}

	// 不相等
	protected function v_notEqualTo($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = false;
		if (isset ($data [ $exp ])) {
			$rst = $value != $data [ $exp ];
		}
		if ($rst) {
			return true;
		} else {
			return empty ($message) ? __('notEqualTo@validator') : sprintf($message, $value);
		}
	}

	// 不相等
	protected function v_notEqual($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = $value != $exp;
		if ($rst) {
			return true;
		} else {
			return empty ($message) ? __('notEqual@validator') : sprintf($message, $value);
		}
	}

	// 数值,包括整数与实数
	protected function v_num($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (is_numeric($value)) {
			return true;
		} else {
			return empty ($message) ? __('num@validator', $value) : vsprintf($message, [$value]);
		}
	}

	protected function v_number($field, $exp, $data, $message) {
		return $this->v_num($field, $exp, $data, $message);
	}

	// 整数
	protected function v_digits($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (preg_match('/^(0|[1-9]\d*)$/', $value)) {
			return true;
		} else {
			return empty ($message) ? __('digits@validator', $value) : vsprintf($message, [$value]);
		}
	}

	// min
	protected function v_min($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$value = floatval($value);
		if ($value >= floatval($exp)) {
			return true;
		} else {
			return empty ($message) ? __('min@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// max
	protected function v_max($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$value = floatval($value);
		if ($value <= floatval($exp)) {
			return true;
		} else {
			return empty ($message) ? __('max@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// gt 大于
	protected function v_gt($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if ($value > $exp) {
			return true;
		} else {
			return empty ($message) ? __('gt@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// ge 大于等于
	protected function v_ge($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if ($value >= $exp) {
			return true;
		} else {
			return empty ($message) ? __('ge@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// gt 小于
	protected function v_lt($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if ($value < $exp) {
			return true;
		} else {
			return empty ($message) ? __('lt@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// ge 小于等于
	protected function v_le($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if ($value <= $exp) {
			return true;
		} else {
			return empty ($message) ? __('le@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// 取值范围
	protected function v_range($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$exp   = explode(',', $exp);
		$value = $data[ $field ];
		if (count($exp) >= 2) {
			$value = floatval($value);
			if ($value >= $exp [0] && $value <= $exp [1]) {
				return true;
			} else {
				return empty ($message) ? __('range@validator', $exp [0], $exp [1]) : vsprintf($message, $exp);
			}
		}

		return true;
	}

	// minlength
	protected function v_minlength($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
		if ($value >= intval($exp)) {
			return true;
		} else {
			return empty ($message) ? __('minlength@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// maxlength
	protected function v_maxlength($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
		if ($value <= intval($exp)) {
			return true;
		} else {
			return empty ($message) ? __('maxlength@validator', $exp) : vsprintf($message, [$exp]);
		}
	}

	// rangelength
	protected function v_rangelength($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$exp   = explode(',', $exp);
		if (count($exp) >= 2) {
			$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
			if ($value >= intval($exp [0]) && $value <= intval($exp [1])) {
				return true;
			} else {
				return empty ($message) ? __('rangelength@validator', $exp [0], $exp [1]) : vsprintf($message, $exp);
			}
		}

		return true;
	}

	// 用户自定义校验函数
	protected function v_callback($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value    = $data[ $field ];
		$callback = $exp ? $exp : false;
		if ($callback && method_exists($this, $callback)) {
			return $this->$callback($value, $data, $message);
		}

		return empty($message) ? __('callback@validator', $callback) : $message;
	}

	protected function v_pattern($field, $exp, $data, $message) {
		return $this->v_regexp($field, $exp, $data, $message);
	}

	// 正则表达式
	protected function v_regexp($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if ($value instanceof ImmutableValue) {
			return true;
		}
		if (@preg_match($exp, $value)) {
			return true;
		} else {
			return empty ($message) ? __('regexp@validator', $value) : sprintf($message, $value);
		}
	}

	// email
	protected function v_email($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (function_exists('filter_var')) {
			$rst = filter_var($value, FILTER_VALIDATE_EMAIL);
		} else {
			$rst = preg_match('/^[_a-z0-9\-]+(\.[_a-z0-9\-]+)*@[a-z0-9][a-z0-9\-]+(\.[a-z0-9-]*)*$/i', $value);
		}

		return $rst ? true : (empty ($message) ? __('email@validator', $value) : sprintf($message, $value));
	}

	// url
	protected function v_url($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (function_exists('filter_var')) {
			$rst = filter_var($value, FILTER_VALIDATE_URL);
		} else {
			$rst = preg_match('/^[a-z]+://[^\s]$/i', $value);
		}

		return $rst ? true : (empty ($message) ? __('URL@validator', $value) : sprintf($message, $value));
	}

	protected function v_ip($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (function_exists('filter_var')) {
			$rst = filter_var($value, FILTER_VALIDATE_IP, $exp == '6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4);
		} else {
			$rst = ip2long($value) === false ? false : true;
		}

		return $rst ? true : (empty ($message) ? __('IP@validator', $value) : sprintf($message, $value));
	}

	protected function v_date($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$sp    = is_string($exp) && strlen($exp) == 1 ? $exp : '-';
		$value = explode($sp, $value);
		if (count($value) == 3 && @checkdate(ltrim($value [1], '0'), ltrim($value [2], '0'), $value [0])) {
			return true;
		}

		return empty ($message) ? __('date@validator', $value) : sprintf($message, $value);
	}

	protected function v_datetime($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$sp    = is_string($exp) && strlen($exp) == 1 ? $exp : '-';
		$times = explode(' ', $value);
		$value = explode($sp, $times [0]);
		if (count($value) == 3 && isset ($times [1]) && @checkdate(ltrim($value [1], '0'), ltrim($value [2], '0'), $value [0])) {
			$time = explode(':', $times [1]);
			if (count($time) == 3 && $time [0] >= 0 && $time [0] < 24 && $time [1] >= 0 && $time [1] < 59 && $time [2] >= 0 && $time [2] < 59) {
				return true;
			}
		}

		return empty ($message) ? __('datetime@validator', $value) : sprintf($message, $value);
	}

	/**
	 * @param string $field
	 * @param array  $data
	 *
	 * @return bool
	 */
	protected function isEmpty($field, $data) {
		if (!isset($data[ $field ])) {
			return true;
		}

		$value = $data[ $field ];

		return is_array($value) ? empty ($value) : strlen(trim($value)) == 0;
	}
}