<?php

namespace wulaphp\auth;

use wulaphp\wulaphp\auth\AclExtraChecker;

/**
 * Class Passport
 * @package wulaphp\auth
 */
class Passport implements \ArrayAccess {
	const SESSION_NAME = 'wula_passport';
	/**@var int $uid */
	public         $uid       = 0;
	public         $type      = 'default';
	public         $username  = '';
	public         $nickname  = '';
	public         $phone     = '';
	public         $email     = '';
	public         $avatar    = '';
	public         $isLogin   = false;
	public         $data      = [];
	public         $error     = null;//错误信息.
	private static $INSTANCES = [];

	/**
	 * Passport constructor.
	 *
	 * @param int $uid
	 */
	public function __construct($uid = 0) {
		$this->uid = $uid;
	}

	/**
	 * @param string $type
	 *
	 * @return Passport
	 */
	public static function get($type = 'default') {
		if (!isset(self::$INSTANCES[ $type ])) {
			$defaultPassport = apply_filter('passport\new' . ucfirst($type) . 'Passport', new Passport());
			$passport        = sess_get(self::SESSION_NAME . '_' . $type);
			if ($passport) {
				if (function_exists('igbinary_unserialize')) {
					self::$INSTANCES[ $type ] = @igbinary_unserialize($passport);
				} else {
					self::$INSTANCES[ $type ] = @unserialize($passport);
				}
			} else {
				$defaultPassport->type    = $type;
				self::$INSTANCES[ $type ] = $defaultPassport;
			}
		}

		return self::$INSTANCES[ $type ];
	}

	/**
	 * 获取密码加密HASH.
	 *
	 * @see password_hash
	 *
	 * @param string $password
	 *
	 * @return string
	 */
	public static function passwd($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * 校验密码是否合法.
	 *
	 * @see password_verify
	 *
	 * @param string $password
	 * @param string $hash
	 *
	 * @return bool
	 */
	public static function verify($password, $hash) {
		return password_verify($password, $hash);
	}

	public function __sleep() {
		$vars = get_object_vars($this);

		return array_keys($vars);
	}

	public function __wakeup() {
		$this->restore();
		fire('passport\restore' . ucfirst($this->type) . 'Passport', $this);
	}

	/**
	 * 获取通行证资料.
	 *
	 * @return array
	 */
	public function info() {
		$info             = $this->data;
		$info['id']       = $this->uid;
		$info['username'] = $this->username;
		$info['nickname'] = $this->nickname;
		$info['phone']    = $this->phone;
		$info['email']    = $this->email;
		$info['avatar']   = $this->avatar;

		return $info;
	}

	/**
	 * 将当前Passport存入SESSION。
	 * @return bool
	 */
	public function store() {
		if (function_exists('igbinary_serialize')) {
			$s = @igbinary_serialize($this);
		} else {
			$s = @serialize($this);
		}
		if ($s) {
			$_SESSION[ self::SESSION_NAME . '_' . $this->type ] = $s;
		}

		return $s ? true : false;
	}

	/**
	 * 从SESSION中注销.
	 */
	public final function logout() {
		fire('passport\on' . ucfirst($this->type) . 'PassportLogout', $this);
		$_SESSION[ self::SESSION_NAME . '_' . $this->type ] = '';
		unset($_SESSION[ self::SESSION_NAME . '_' . $this->type ]);
	}

	/**
	 * 登录
	 *
	 * @param mixed $data 登录验证使用的数据
	 *
	 * @return bool
	 */
	public final function login($data = null) {
		$this->isLogin = $this->doAuth($data);
		if ($this->isLogin) {
			fire('passport\on' . ucfirst($this->type) . 'PassportLogin', $this);
			$this->store();
		}

		return $this->isLogin;
	}

	/**
	 * 用户是否有权限操作。
	 *
	 * @param string $res
	 * @param null   $extra
	 *
	 * @return bool
	 */
	public function cando($res, $extra = null) {
		$resid = explode(':', $res);
		$op    = $resid[0];
		if (!isset($resid[1])) {
			return false;
		}
		$rid = str_replace('/', '\\', $resid[1]);
		$rst = $this->checkAcl($op, $resid[1], $extra);
		if ($rst) {
			//额外权限检测
			$aclExtraChecker = apply_filter('rbac\getExtraChecker\\' . $rid, null);
			if ($aclExtraChecker instanceof AclExtraChecker) {
				$rst = $aclExtraChecker->check($this, $op, $extra);
			}
		}

		return $rst;
	}

	/**
	 * 权限校验.
	 *
	 * @param string $op
	 * @param string $res
	 * @param array  $extra
	 *
	 * @return bool
	 */
	protected function checkAcl($op, $res, $extra) {
		return true;
	}

	/**
	 * 当前用户是否是$role角色.
	 *
	 * @param string|array $roles
	 *
	 * @return bool
	 */
	public function is($roles) {
		return true;
	}

	/**
	 * 登录认证.
	 *
	 * @param mixed $data 验证使用的数据
	 *
	 * @return bool 认证成功返回true,反之返回false.
	 */
	protected function doAuth($data = null) {
		return false;
	}

	/**
	 * 从session中恢复后调用。
	 * 可以用来更新需要实时修改的数据。
	 */
	protected function restore() {

	}

	public function __toString() {
		return $this->nickname;
	}

	public function offsetExists($offset) {
		if (isset($this->{$offset})) {
			return true;
		} else if (isset($this->data[ $offset ])) {
			return true;
		}

		return false;
	}

	public function offsetGet($offset) {
		if (isset($this->{$offset})) {
			return $this->{$offset};
		} else if (isset($this->data[ $offset ])) {
			return $this->data[ $offset ];
		}

		return null;
	}

	public function offsetSet($offset, $value) {
	}

	public function offsetUnset($offset) {
	}
}