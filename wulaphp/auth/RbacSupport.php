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
 * @property Passport          $passport
 * @property \ReflectionObject $reflectionObj
 */
trait RbacSupport {
	private $globalRbacSetting = ['login' => false];

	protected function onInitRbacSupport() {
		if ($this->reflectionObj instanceof \ReflectionObject) {
			$ann                               = new Annotation($this->reflectionObj);
			$this->globalRbacSetting['login']  = $ann->has('login');
			$this->globalRbacSetting['roles']  = $ann->getArray('roles');
			$this->globalRbacSetting['acl']    = $ann->getArray('acl');
			$this->globalRbacSetting['aclmsg'] = $ann->getString('aclmsg');
		}
	}

	/**
	 * @param \Reflector $method
	 *
	 */
	protected function beforeRunInRbacSupport(\Reflector $method) {
		if ($this->passport instanceof Passport) {
			$annotation = new Annotation($method);
			$nologin    = $annotation->has('login');
			if ($nologin) {//不需要登录
				return;
			}

			$login = $annotation->has('login') || $this->globalRbacSetting['login'];

			if ($annotation->has('acl')) {
				$acl = $annotation->getArray('acl');
			} else {
				$acl = $this->globalRbacSetting['acl'];
			}

			if ($annotation->has('roles')) {
				$roles = $annotation->getArray('roles');
			} else {
				$roles = $this->globalRbacSetting['roles'];
			}

			$login = $login || $acl || $roles;
			if ($login && !$this->passport->isLogin) {
				$this->needLogin();
			}
			$rst = true;
			if ($acl) {
				$res = array_shift($acl);
				$rst = $this->passport->cando($res, $acl);
			}
			// 同时还要有角色 $roles
			if ($rst && $roles) {
				$rst = $this->passport->is($roles);
			}

			if (!$rst) {
				$msg = $annotation->getString('aclmsg') || $this->globalRbacSetting['aclmsg'];

				$this->onDenied($msg);
			}
		} else {
			$this->onDenied($this->globalRbacSetting['aclmsg']);
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