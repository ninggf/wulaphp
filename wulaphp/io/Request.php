<?php
namespace wulaphp\io;

/**
 *
 * 基本的请求处理类
 * 提供从$_POST和$_GET中取数据的能力,同时可以进行XSS过滤.
 * 另外cookie也可以通过本类的实例来获取.
 *
 * @author Windywany
 * @package kissgo
 *          @date 12-9-16 下午5:36
 *          $Id$
 */
class Request implements \ArrayAccess {

    private $userData = array ();

    private $getData = array ();

    private $postData = array ();

    protected $use_xss_clean = false;

    private static $xss_cleaner;

    private $quotes_gpc;

    private static $INSTANCE = null;

    private static $UUID = false;

    private static $SESSION_STARTED = false;

    public static $_GET = array ();

    public static $_POST = array ();

    private function __construct($xss_clean = true) {
        $this->use_xss_clean = $xss_clean;
        $this->quotes_gpc = get_magic_quotes_gpc ();
        if (Request::$xss_cleaner == null) {
            Request::$xss_cleaner = new \ci\XssCleaner ();
        }
        $this->_sanitize_globals ();
    }

    /**
     * 得到request的实例.
     *
     * @return Request
     */
    public static function getInstance($use_xss_clean = null) {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new self ( $use_xss_clean );
        }
        if (is_bool ( $use_xss_clean )) {
            self::$INSTANCE->set_cleaner_enable ( $use_xss_clean );
        }
        return self::$INSTANCE;
    }

    /**
     * 本次请求的类型
     *
     * @return bool 如果是通过ajax请求的返回true,反之返回false
     */
    public static function isAjaxRequest() {
        return isset ( $_SERVER ["HTTP_X_AJAX_TYPE"] ) || (isset ( $_SERVER ['HTTP_X_REQUESTED_WITH'] ) && strtolower ( $_SERVER ['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest');
    }

    public static function isGet() {
        return strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == 'GET';
    }

    public static function isPost() {
        return strtoupper ( $_SERVER ['REQUEST_METHOD'] ) == 'POST';
    }

    /**
     * set enable flag
     *
     * @param $enable
     */
    public function set_cleaner_enable($enable) {
        $this->use_xss_clean = $enable;
    }

    /**
     * 获取客户端传过来的值无论是通过GET方式还是POST方式
     *
     * @param string $name
     * @param mixed $default
     * @param boolean $xss_clean 是否进行xss过滤
     * @return mixed
     */
    public function get($name, $default = '', $xss_clean = false) {
        if (! $this->use_xss_clean) {
            $ary = isset ( $this->userData [$name] ) ? $this->userData : (isset ( $this->postData [$name] ) ? $this->postData : $this->getData);
        } else if ($xss_clean) {
            $ary = isset ( $this->userData [$name] ) ? $this->userData : (isset ( $_POST [$name] ) ? $_POST : $_GET);
        } else {
            $ary = isset ( $this->userData [$name] ) ? $this->userData : (isset ( $this->postData [$name] ) ? $this->postData : $this->getData);
        }
        if (! isset ( $ary [$name] ) || (! is_numeric ( $ary [$name] ) && empty ( $ary [$name] ))) {
            return $default;
        }
        return $ary [$name];
    }

    public function addUserData($data = array(), $reset = false) {
        if (is_array ( $data ) && $data) {
            if ($reset) {
                $this->userData = $data;
            } else {
                $this->userData = array_merge ( $this->userData, $data );
            }
        }
    }

    public function getUserData() {
        return $this->userData;
    }

    public function initRawPost() {
        $in = @fopen ( 'php://input', 'rb' );
        if ($in) {
            $tmp = [ ];
            do {
                $buff = fread ( $in, 4096 );
                if ($buff) {
                    $tmp [] = $buff;
                }
            } while ( $buff );
            if ($tmp) {
                $data = @json_decode ( implode ( '', $tmp ), true );
                if ($data) {
                    $data = $this->_clean_input_data ( $data );
                    $this->addUserData ( $data );
                }
                unset ( $tmp, $data );
            }
            @fclose ( $in );
        }
    }

    public static function getIp() {
        if (! empty ( $_SERVER ["HTTP_CLIENT_IP"] )) {
            $cip = $_SERVER ["HTTP_CLIENT_IP"];
        } elseif (! empty ( $_SERVER ["HTTP_X_FORWARDED_FOR"] )) {
            $cip = $_SERVER ["HTTP_X_FORWARDED_FOR"];
        } elseif (! empty ( $_SERVER ["REMOTE_ADDR"] )) {
            $cip = $_SERVER ["REMOTE_ADDR"];
        } else {
            $cip = "";
        }
        return $cip;
    }

    /**
     * 对值进行xss安全处理.
     *
     * @param $val 要进行xss处理的值
     * @return string
     */
    public static function xss_clean($val) {
        if (Request::$xss_cleaner == null) {
            Request::$xss_cleaner = new \ci\XssCleaner ();
        }
        $val = Request::$xss_cleaner->xss_clean ( $val );
        return $val;
    }

    public static function setUUID() {
        if (isset ( $_COOKIE ['_m_Uuid_'] )) {
            self::$UUID = $_COOKIE ['_m_Uuid_'];
            return;
        }
        self::$UUID = uniqid ();
        // 2 years = 63072000
        @setcookie ( '_m_Uuid_', self::$UUID, time () + 63072000, '/', '', false, true );
    }

    public static function getUUID() {
        return self::$UUID;
    }

    public function offsetExists($offset) {
        return isset ( $_GET [$offset] ) || isset ( $_POST [$offset] ) || isset ( $this->userData [$offset] );
    }

    public function offsetGet($offset) {
        return $this->get ( $offset );
    }

    public function offsetSet($offset, $value) {
        $this->userData [$offset] = $value;
    }

    public function offsetUnset($offset) {
        if (isset ( $this->userData [$offset] )) {
            unset ( $this->userData [$offset] );
        }
    }
    // 处理全局输入
    private function _sanitize_globals() {
        Request::$_GET = $_GET;
        Request::$_POST = $_POST;
        $this->getData = array_merge ( array (), $_GET );
        $this->postData = array_merge ( array (), $_POST );
        $_GET = $this->_clean_input_data ( $_GET );
        $_POST = $this->_clean_input_data ( $_POST );
        $_REQUEST = $this->_clean_input_data ( $_REQUEST );
        unset ( $_COOKIE ['$Version'] );
        unset ( $_COOKIE ['$Path'] );
        unset ( $_COOKIE ['$Domain'] );
        $_COOKIE = $this->_clean_input_data ( $_COOKIE );
    }

    private function _clean_input_data($str) {
        if (is_array ( $str )) {
            $new_array = array ();
            foreach ( $str as $key => $val ) {
                $new_array [$this->_clean_input_keys ( $key )] = $this->_clean_input_data ( $val );
            }
            return $new_array;
        }
        
        // We strip slashes if magic quotes is on to keep things consistent
        if ($this->quotes_gpc) {
            $str = stripslashes ( $str );
        }
        
        // Should we filter the input data?
        if ($this->use_xss_clean === true) {
            $str = Request::$xss_cleaner->xss_clean ( $str );
        }
        
        // Standardize newlines
        if (strpos ( $str, "\r" ) !== FALSE) {
            $str = str_replace ( array (
                "\r\n","\r"
            ), "\n", $str );
        }
        
        return $str;
    }

    private function _clean_input_keys($str) {
        if (! preg_match ( '/^[a-z0-9:_\-\/-\\\\*]+$/i', $str )) {
            log_error ( 'Disallowed Key Characters:' . $str );
            exit ( 'Disallowed Key Characters:' . $str );
        }
        return $str;
    }
}
