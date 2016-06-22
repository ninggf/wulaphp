<?php
namespace wulaphp\rbac;

/**
 * Passport.
 *
 * @author Guangfeng Ning <windywany@gmail.com>
 * @package core
 */
class Passport implements \Serializable, \ArrayAccess {

    private static $INSTANCE = array ();

    private $time = 0;

    private $ip = '';

    private $uid = 0;

    private $account = '';

    private $user_name = '';
    private $nick_name = '';

    private $email = '';

    private $avatar = '';

    private $registered = 0;

    private $landings = array ();

    private $attrs = array ();

    private $isLogin = false;

    private $type;

    private function __construct($uid = 0, $type = 'admin') {
        $this->uid = $uid;
        $this->type = $type;
    }

    public function isLogin($login = null) {
        if (is_null ( $login )) {
            return $this->isLogin;
        } else {
            $this->isLogin = $login ? true : false;
            if ($this->isLogin) {
                $this->time = time ();
            }
            return $this->isLogin;
        }
    }
    public function logout() {
        fire ( 'on_passport_logout', $this );
        sess_del ( '_passport' . $this->type );
    }

    public function save($user = false) {
        if ($user) {
            $this->setAccount ( $user ['username'] );
            $this->setEmail ( $user ['email'] );
            $this->setUserName ( $user ['nickname'] );
            $this->setRegistered ( $user ['registered'] );
            $this->isLogin ( $user ['logined'] );
            if ($user ['group_id'] && ($this->type == 'admin' || $this->type == 'vip')) {
                $group = dbselect ( 'parents,subgroups,group_name,group_refid' )->from ( '{user_group}' )
                    ->where ( array (
                    'group_id' => $user ['group_id']
                ) )
                    ->get ( 0 );
                if ($group) {
                    $this->setAttr ( 'upgroups', explode ( ',', $group ['parents'] ) );
                    $this->setAttr ( 'subgroups', explode ( ',', $group ['subgroups'] ) );
                    $this->setAttr ( 'group_name', $group ['group_name'] );
                    $this->setAttr ( 'group_refid', $group ['group_refid'] );
                }
            }
            if ($this->type == 'admin') {
                $this->setUid ( $user ['user_id'] );
                $metas = dbselect ( 'meta_name,meta_value' )->from ( '{user_meta}' )
                    ->where ( array (
                    'user_id' => $user ['user_id']
                ) )
                    ->toArray ( 'meta_value', 'meta_name' );
                if ($metas) {
                    foreach ( $metas as $key => $value ) {
                        $this->setAttr ( $key, $value );
                    }
                }
            } else {
                fire ( 'on_save_user_passport_' . $this->type, $this, $user );
            }
            foreach ( $user as $key => $val ) {
                $this->setAttr ( $key, $val );
            }
        }
        $_SESSION ['_USER_LoginInfo_' . $this->type] = $this;
    }

    /**
     *
     * @return the $time
     */
    public function getTime() {
        return $this->time;
    }

    /**
     *
     * @return the $ip
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     *
     * @return the $uid
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     *
     * @return the $account
     */
    public function getAccount() {
        return $this->account;
    }

    /**
     *
     * @return the $user_name
     */
    public function getUserName() {
        return $this->user_name;
    }

    /**
     *
     * @return the $display_name
     */
    public function getDisplayName() {
        return $this->user_name;
    }

    /**
     *
     * @return the $email
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     *
     * @return the $avatar
     */
    public function getAvatar() {
        return $this->avatar;
    }

    /**
     *
     * @return the $landings
     */
    public function getLandings() {
        return $this->landings;
    }

    /**
     *
     * @return the $registered
     */
    public function getRegistered() {
        return $this->registered;
    }

    /**
     * get attribute.
     *
     * @param string $attr
     * @param multitype $default
     * @return multitype: string
     */
    public function getAttr($attr, $default = '') {
        if (isset ( $this->attrs [$attr] )) {
            return $this->attrs [$attr];
        } else {
            return $default;
        }
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->logout ();
        $this->type = $type;
    }

    /**
     *
     * @param number $time
     */
    public function setTime($time) {
        $this->time = $time;
    }

    /**
     *
     * @param string $ip
     */
    public function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     *
     * @param number $uid
     */
    public function setUid($uid) {
        $this->uid = $uid;
    }

    /**
     *
     * @param string $account
     */
    public function setAccount($account) {
        $this->account = $account;
    }

    /**
     *
     * @param string $user_name
     */
    public function setUserName($user_name) {
        $this->user_name = $user_name;
    }

    /**
     *
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     *
     * @param string $avatar
     */
    public function setAvatar($avatar) {
        $this->avatar = $avatar;
    }

    /**
     *
     * @param multitype: $landings
     */
    public function setLandings($landings) {
        $this->landings = $landings;
    }

    /**
     *
     * @param number $registered
     */
    public function setRegistered($registered) {
        $this->registered = $registered;
    }

    /**
     *
     * @param string $attr
     * @param mixed $value
     */
    public function setAttr($attr, $value) {
        if ($value == null && isset ( $this->attrs [$attr] )) {
            unset ( $this->attrs [$attr] );
        } else {
            $this->attrs [$attr] = $value;
        }
    }

    public function serialize() {
        $data = array ();
        $data ['time'] = $this->time;
        $data ['ip'] = $this->ip;
        $data ['uid'] = $this->uid;
        $data ['account'] = $this->account;
        $data ['username'] = $this->user_name;
        $data ['email'] = $this->email;
        $data ['avatar'] = $this->avatar;
        $data ['landings'] = $this->landings;
        $data ['isLogin'] = $this->isLogin;
        $data ['type'] = $this->type;
        $data ['attrs'] = $this->attrs;
        return json_encode ( $data );
    }

    public function unserialize($serialized) {
        $data = @json_decode ( $serialized, true );
        if ($data) {
            $this->time = $data ['time'];
            $this->ip = $data ['ip'];
            $this->uid = $data ['uid'];
            $this->account = $data ['account'];
            $this->user_name = $data ['username'];
            $this->email = $data ['email'];
            $this->avatar = $data ['avatar'];
            $this->landings = $data ['landings'];
            $this->isLogin = $data ['isLogin'];
            $this->attrs = $data ['attrs'];
            $this->type = empty ( $data ['type'] ) ? 'admin' : $data ['type'];
        }
        return $this;
    }

    /**
     * get the u
     *
     * @param int $uid 用户ID
     * @return Passport 用户护照
     */
    public static function getPassport($uid = 0, $type = 'admin') {
        $uid = intval ( $uid );
        if (! isset ( self::$INSTANCE [$type] [$uid] )) {
            if (isset ( $_SESSION ['_USER_LoginInfo_' . $type] )) {
                $passport = $_SESSION ['_USER_LoginInfo_' . $type];
            } else {
                $passport = new Passport ( $uid, $type );
                $passport = apply_filter ( 'get_user_passport_' . $type, $passport );
            }
            self::$INSTANCE [$type] [$uid] = $passport;
        }
        return self::$INSTANCE [$type] [$uid];
    }

    /**
     * 取RBAC驱动.
     *
     * @param string $type
     * @return Ambigous <mixed>
     */
    public static function getDriver($type = 'admin') {
        static $drivers = array ();
        if (! isset ( $drivers [$type] ) || ! $drivers [$type] instanceof IRbac) {
            $drivers [$type] = apply_filter ( 'get_rbac_driver', null, $type );
        }
        return $drivers [$type];
    }
    /*
     * (non-PHPdoc) @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset) {
        return isset ( $this->{$offset} ) || isset ( $this->attrs [$offset] );
    }
    
    /*
     * (non-PHPdoc) @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset) {
        if (isset ( $this->{$offset} )) {
            return $this->{$offset};
        } else if (isset ( $this->attrs [$offset] )) {
            return $this->attrs [$offset];
        } else {
            return '';
        }
    }
    
    /*
     * (non-PHPdoc) @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value) {
        $this->attrs [$offset] = $value;
    }
    
    /*
     * (non-PHPdoc) @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset) {
        unset ( $this->attrs [$offset] );
    }
}