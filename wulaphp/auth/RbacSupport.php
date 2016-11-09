<?php

namespace wulaphp\auth;

use wulaphp\util\Annotation;

/**
 * 权限认证特性，此特性依赖AuthSupport的子类.
 *
 * 可通过Annotation指定每个方法的权限。
 *
 * @package wulaphp\auth
 *
 * @property Passport $passport
 */
trait RbacSupport {

	/**
	 * @param \Reflector $method
	 */
	protected function beforeRunInRbacSupport(\Reflector $method) {
		if ($this->passport) {
			$annotation = new Annotation($method);
			$login      = $annotation->has('login');
			$acl        = $annotation->getArray('acl');
			$roles      = $annotation->getArray('roles');
			$login      = $login || $acl || $roles;
			if ($login && !$this->passport->isLogin) {
				$this->needLogin();
			}
			$rst = false;
			if ($acl) {
				$res = array_shift($acl);
				$rst = $this->passport->cando($res, $acl);
			} elseif ($roles) {
				$rst = $this->passport->is($roles);
			}
			if (!$rst) {
				$msg = $annotation->getString('aclmsg');
				$this->onDenied($msg);
			}
		} else {
			$this->onDenied('');
		}
	}

	/**
	 * 未登录时.
	 *
	 * @return string
	 */
	protected abstract function needLogin();

	/**
	 * 用户无权限时.
	 *
	 * @param string $message
	 */
	protected abstract function onDenied($message);
}