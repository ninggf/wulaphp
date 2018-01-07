<?php

namespace wulaphp\auth;

use wulaphp\mvc\view\SmartyView;

/**
 * 用户通行证认证特性。添加此特性后，在控制器中可直接通过$passport属性访问当前用户的通行证.
 *
 * 注：此特性依赖SessionSupport.
 *
 * @package wulaphp\auth
 * @property string $passportType
 */
trait PassportSupport {
	/**
	 * @var Passport
	 */
	protected $passport;

	protected function onInitPassportSupport() {
		if (isset($this->passportType)) {
			$this->passport = Passport::get($this->passportType);
		} else {
			trigger_error('not set passportType property in Controller:' . get_class($this) . ', use default passport', E_USER_WARNING);
			$this->passport = Passport::get();
		}
	}

	public function afterRunInPassportSupport($action, $view, $method) {
		if ($view instanceof SmartyView) {
			$view->assign('myPassport', $this->passport);
		}

		return $view;
	}
}