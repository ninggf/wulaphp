<?php

namespace wulaphp\auth;

/**
 * Class Passport
 * @package wulaphp\auth
 * @property-read int $pid          父ID
 * @property-read int $status       状态
 * @property-read int $screenLocked 屏幕锁定时间
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
    public final static function get(string $type = 'default'):Passport {
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
     * @param string $password
     *
     * @return string
     * @see password_hash
     *
     */
    public static function passwd(string $password):string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 校验密码是否合法.
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     * @see password_verify
     *
     */
    public static function verify(string $password,string $hash):bool {
        return password_verify($password, $hash);
    }

    public function __sleep() {
        $vars = get_object_vars($this);

        return array_keys($vars);
    }

    public function __wakeup() {
        $this->restore();
        try {
            fire('passport\restore' . ucfirst($this->type) . 'Passport', $this);
        } catch (\Exception $e) {

        }
    }

    /**
     * 获取通行证资料.
     *
     * @return array
     */
    public function info():array {
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
        $this->isLogin = false;
        $this->uid     = 0;
        try {
            fire('passport\on' . ucfirst($this->type) . 'PassportLogout', $this);
        } catch (\Exception $e) {

        }
        $_SESSION[ self::SESSION_NAME . '_' . $this->type ] = '';
        unset($_SESSION[ self::SESSION_NAME . '_' . $this->type ]);
    }

    /**
     * 登录
     *
     * @param mixed $data 登录验证使用的数据
     *
     * @return bool
     * @throws \Exception
     */
    public final function login($data = null):bool {
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
     * @param string $opRes 操作资源
     * @param null|array   $extra
     *
     * @return bool
     */
    public final function cando(string $opRes,?array $extra = null):bool {
        $resid = explode(':', $opRes);
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
     * 是不是超级用户。
     *
     * @return bool 超级用户返回true,反之返回false。
     */
    public function isSuper() {
        if (isset($this->data['pid'])) {
            return $this->data['pid'] == $this->uid;
        }

        return true;
    }

    /**
     * 锁定屏幕.
     */
    public final function lockScreen() {
        $this->data['screenLocked'] = time();
        $this->store();
    }

    /**
     * 解锁屏幕.
     *
     * @param string $password
     *
     * @return bool
     */
    public final function unlockScreen($password) {
        if ($this->verifyPasswd($password)) {
            $this->data['screenLocked'] = 0;
            $this->store();

            return true;
        }

        return false;
    }

    /**
     * 验证用户密码。
     *
     * @param string $password
     *
     * @return bool
     */
    protected function verifyPasswd($password) {
        return false;
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

    public final function __toString() {
        return $this->nickname;
    }

    public final function __get($name) {
        if (isset($this->{$name})) {
            return $this->{$name};
        } else if (isset($this->data[ $name ])) {
            return $this->data[ $name ];
        }

        return null;
    }

    public final function offsetGet($offset) {
        if (isset($this->{$offset})) {
            return $this->{$offset};
        } else if (isset($this->data[ $offset ])) {
            return $this->data[ $offset ];
        }

        return null;
    }

    public final function offsetExists($offset) {
        if (isset($this->{$offset})) {
            return true;
        } else if (isset($this->data[ $offset ])) {
            return true;
        }

        return false;
    }

    public final function offsetSet($offset, $value) {
        $this->data[ $offset ] = $value;
    }

    public final function offsetUnset($offset) {
        unset($this->data[ $offset ]);
    }
}