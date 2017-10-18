<?php

namespace wulaphp\validator;

use wulaphp\db\sql\ImmutableValue;

/**
 * 数据检验器.
 * @package wulaphp\validator
 *
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 * @property array $_fields
 * @property array $_formData
 * @method array inflate()
 */
trait Validator {
	private $rules    = [];
	private $rulesIdx = [];
	private $ruleKeys = [];
	//预定义的验证规则
	private $preDefinedRule = [
		'required'           => true,
		'equalTo'            => true,
		'notEqualTo'         => true,
		'num'                => true,
		'number'             => true,
		'digits'             => true,
		'min'                => true,
		'max'                => true,
		'phone'              => true,
		'range'              => true,
		'minlength'          => true,
		'maxlength'          => true,
		'rangelength'        => true,
		'callback'           => true,
		'pattern'            => true,
		'email'              => true,
		'url'                => true,
		'ip'                 => true,
		'ipv6'               => true,
		'date'               => true,
		'datetime'           => true,
		'step'               => true,
		'rangeWords'         => true,
		'minWords'           => true,
		'maxWords'           => true,
		'require_from_group' => true,
		'passwd'             => true
	];

	/**
	 * 初始化验证器.
	 *
	 * @param array $fields 要验证的字段.
	 */
	protected final function onInitValidator($fields = []) {
		if (empty($fields)) {
			if (isset($this->_fields) && $this->_fields) {
				$fields = $this->_fields;
			}
		}
		if ($fields) {
			foreach ($fields as $field => $def) {
				$rule = [];
				/**@var $ann \wulaphp\util\Annotation */
				$ann      = $def['annotation'];
				$anns     = $ann->getAll();
				$multiple = $def['type'] == 'array' || $def['type'] == '[]';
				foreach ($anns as $an => $va) {
					if (isset($this->preDefinedRule[ $an ]) && !is_array($va)) {
						// 用=>分隔参数与提示消息.$r[0]参数,$r[1]为提示信息
						$r   = $va ? preg_split('/\s*=>\s*/u', $va) : [];
						$len = count($r);
						if ($len == 0) {
							$rule[ $an ] = null;
						} else {
							if (preg_match('/^\(.+\)$/', trim($r[0]))) {
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
					$this->addRule($field, $rule, $multiple);
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
	public final function getValidateRules(...$fileds) {
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
	 * @param array $rules 验证规则.如果为空则使用之前的规则.
	 *
	 * @return bool
	 * @throws ValidateException
	 */
	public final function validate(array $data = null, array $rules = null) {
		if ($data === null && isset($this->_formData)) {
			$data = $this->_formData;
		}
		if (empty($data)) {
			throw new ValidateException(['error' => 'data is empty']);
		}
		if ($rules) {
			$this->rules    = [];
			$this->rulesIdx = [];
			$this->ruleKeys = [];
			foreach ($rules as $field => $rule) {
				$this->addRule($field, $rule);
			}
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
	protected final function validateNewData(array $data = null) {
		if ($data === null && isset($this->_formData)) {
			$data = $this->_formData;
		}
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
	protected final function validateUpdateData(array $data = null) {
		if ($data === null && isset($this->_formData)) {
			$data = $this->_formData;
		}
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
	 * @param string  $field
	 * @param array   $rules
	 * @param boolean $multiple
	 */
	public final function addRule($field, array $rules, $multiple = false) {
		foreach ($rules as $rule => $m) {
			if (is_array($m)) {
				@list($r, $ops, $m, $multiple) = $m;
			} else {
				if (is_int($rule)) {
					$rule = $m;
					list($r, $ops) = $this->parseRule($rule);
					$m = '';
				} else {
					list($r, $ops) = $this->parseRule($rule);
				}
			}
			if ($r) {
				$this->rulesIdx[ $field ]       += 1;
				$idx                            = $this->rulesIdx[ $field ];
				$this->rules[ $field ][ $idx ]  = [$r, $ops, $m, $multiple];
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
	public final function removeRule($field, $rule = null) {
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
				if ($msg) {
					$msg = preg_replace('#\{\d+?\}#', '%s', $msg);
				}
				$valid = $this->$valid_m ($field, $ops, $data, $msg);
			} else {
				return _t('notsupportmethod@validator', $field, $m);
			}
			if ($valid !== true) {
				return $valid;
			}
		}

		return true;
	}

	/**
	 * 解析验证规则.
	 *
	 * @param string $rule
	 *
	 * @return array
	 */
	private function parseRule($rule) {
		$parsed = [false];
		if (preg_match('#^([a-z][a-z\d_]+)\s*\((.+)\)\s*$#i', $rule, $ms)) {
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
					if (!$rst) {
						break;
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
			return empty ($message) ? _t('required@validator') : $message;
		}

		$value = $data[ $field ];
		if ($value instanceof ImmutableValue) {
			return true;
		}

		if (!$this->isEmpty($field, $data)) {
			return true;
		} else {
			return empty ($message) ? _t('required@validator') : $message;
		}
	}

	// 和另一个字段的值相等
	protected function v_equalTo($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = false;
		$exp   = ltrim($exp, '#');
		if (isset ($data [ $exp ])) {
			$rst = $value == $data [ $exp ];
		}
		if ($rst) {
			return true;
		} else {
			return empty ($message) ? _t('equalTo@validator') : $message;
		}
	}

	// 和另一个字段的值不相等
	protected function v_notEqualTo($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = false;
		$v1    = null;
		$exp   = ltrim($exp, '#');
		if (isset ($data [ $exp ])) {
			$v1  = $data [ $exp ];
			$rst = $value != $v1;
		}
		if ($rst) {
			return true;
		} else {
			return empty ($message) ? _t('notEqualTo@validator') : $message;
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
			return empty ($message) ? _t('num@validator') : $message;
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
			return empty ($message) ? _t('digits@validator') : $message;
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
			return empty ($message) ? _t('min@validator', $exp) : sprintf($message, $exp);
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
			return empty ($message) ? _t('max@validator', $exp) : sprintf($message, $exp);
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
				return empty ($message) ? _t('range@validator', $exp [0], $exp [1]) : vsprintf($message, $exp);
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
		if (is_array($value)) {
			$value = count($value);
		} else {
			$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
		}
		if ($value >= intval($exp)) {
			return true;
		} else {
			return empty ($message) ? _t('minlength@validator', $exp) : sprintf($message, $exp);
		}
	}

	// maxlength
	protected function v_maxlength($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (is_array($value)) {
			$value = count($value);
		} else {
			$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
		}
		if ($value <= intval($exp)) {
			return true;
		} else {
			return empty ($message) ? _t('maxlength@validator', $exp) : sprintf($message, $exp);
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
			if (is_array($value)) {
				$value = count($value);
			} else {
				$value = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
			}
			if ($value >= intval($exp [0]) && $value <= intval($exp [1])) {
				return true;
			} else {
				return empty ($message) ? _t('rangelength@validator', $exp [0], $exp [1]) : vsprintf($message, $exp);
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
		$callback = $exp ? trim($exp) : false;
		$callback = $callback ? preg_replace('/(.+?)(\s*\(.*\))?$/', '\1', $callback) : false;
		if ($callback && method_exists($this, $callback)) {
			return $this->$callback($value, $data, $message);
		}

		return empty($message) ? _t('callback@validator') : $message;
	}

	// 正则表达式
	protected function v_pattern($field, $exp, $data, $message) {
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
			return empty ($message) ? _t('pattern@validator') : $message;
		}
	}

	protected function v_phone($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$rst   = preg_match('/^1[34578]\d{9}$/', $value);

		return $rst ? true : (empty ($message) ? _t('phone@validator') : $message);
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

		return $rst ? true : (empty ($message) ? _t('email@validator') : $message);
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

		return $rst ? true : (empty ($message) ? _t('URL@validator', $value) : $message);
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

		return $rst ? true : (empty ($message) ? _t('IP@validator') : $message);
	}

	protected function v_ipv6($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		if (function_exists('filter_var')) {
			$rst = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
		} else {
			$rst = preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i', $value);
		}

		return $rst ? true : (empty ($message) ? _t('IP@validator') : $message);
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

		return empty ($message) ? _t('date@validator') : $message;
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

		return empty ($message) ? _t('datetime@validator') : $message;
	}

	protected function v_step($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$exp   = intval($exp);
		if ($exp) {
			if ($value % $exp == 0) {
				return true;
			}

			return (empty ($message) ? _t('step@validator', $exp) : sprintf($message, $exp));
		}

		return true;
	}

	protected function v_minWords($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$exp   = intval($exp);
		$words = preg_split('/\b\w+\b/u', $value);
		if (count($words) >= $exp) {
			return true;
		}

		return (empty ($message) ? _t('minWords@validator', $exp) : sprintf($message, $exp));
	}

	protected function v_maxWords($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$exp   = intval($exp);
		$words = preg_split('/\b\w+\b/u', $value);
		if (count($words) <= $exp) {
			return true;
		}

		return (empty ($message) ? _t('maxWords@validator', $exp) : sprintf($message, $exp));
	}

	protected function v_rangeWords($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];
		$exp   = explode(',', $exp);
		@list($e1, $e2) = $exp;
		$exp[1] = $e2;
		$words  = preg_split('/\b\w+\b/u', $value);
		$len    = count($words);
		if ($len >= $e1 && $len <= $e2) {
			return true;
		}

		return (empty ($message) ? _t('maxWords@validator', $exp) : sprintf($message, $e1, $e2));
	}

	protected function v_require_from_group($field, $exp, $data, $message) {
		$exp = explode(',', $exp);
		if (count($exp) > 2) {
			$cnt = intval($exp[0]);
			$exp = array_slice($exp, 2);
			foreach ($exp as $f) {
				if (isset($data[ $f ]) && ($data[ $f ] || is_numeric($data[ $f ]))) {
					$cnt--;
				}
			}
			if ($cnt <= 0) {
				return true;
			}

			return $message;
		}

		return true;
	}

	protected function v_passwd($field, $exp, $data, $message) {
		if ($this->isEmpty($field, $data)) {
			return true;
		}
		$value = $data[ $field ];

		switch ($exp) {
			case '2'://有字母，数字，符号
				$rst = preg_match('/[a-z]/i', $value) && preg_match('/\d/', $value) && preg_match('/[^a-z\d]/i', $value);
				break;
			case '3':
				$rst = preg_match('/[A-Z]/', $value) && preg_match('/[a-z]/', $value) && preg_match('/\d/', $value) && preg_match('/[^a-z\d]/i', $value);
				//有小写字母，大写字母，数字，符号
				break;
			case '1'://有字母，数字
			default:
				$exp = '';
				$rst = preg_match('/[a-z]/i', $value) && preg_match('/\d/', $value);
		}
		if ($rst) {
			return true;
		}

		return (empty ($message) ? _t('password' . $exp . '@validator') : $message);
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