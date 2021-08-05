<?php

namespace wulaphp\io;

use ci\XssCleaner;

/**
 *
 * 基本的请求处理类
 * 提供从$_POST和$_GET中取数据的能力,同时可以进行XSS过滤.
 * 另外cookie也可以通过本类的实例来获取.
 *
 * @author  Windywany
 * @package kissgo
 * @date    12-9-16 下午5:36
 *          $Id$
 */
class Request implements \ArrayAccess {
    private $userData    = [];
    private $requestData = [];
    private $arrayData   = [];
    /**
     * @var XssCleaner
     */
    private static $xss_cleaner = null;
    private static $INSTANCE    = null;
    private static $santitized  = false;
    private static $UUID        = false;

    private function __construct() {
        if (self::$xss_cleaner == null) {
            self::$xss_cleaner = new XssCleaner();
        }
        if (!self::$santitized) {
            self::$santitized = true;
            $this->sanitizeGlobals();
        }
        $this->addJsonPostBody();
    }

    /**
     * 得到request的实例.
     *
     * @return Request
     */
    public static function getInstance(): Request {
        if (defined('ARTISAN_TASK_PID')) {
            $pid = @posix_getpid();
            if (!isset(self::$INSTANCE[ $pid ])) {
                self::$INSTANCE[ $pid ] = new self ();
            }

            return self::$INSTANCE[ $pid ];
        } else {
            if (self::$INSTANCE == null) {
                self::$INSTANCE = new self ();
            }

            return self::$INSTANCE;
        }
    }

    /**
     * 本次请求是否是ajax请求
     *
     * @return bool 如果是通过ajax请求的返回true,反之返回false
     */
    public static function isAjaxRequest(): bool {
        return isset ($_SERVER ["HTTP_X_AJAX_TYPE"]) || (isset ($_SERVER ['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * 本次请求是否是ajax请求
     *
     * @return bool 如果是通过ajax请求的返回true,反之返回false
     */
    public static function isAjax(): bool {
        return isset ($_SERVER ["HTTP_X_AJAX_TYPE"]) || (isset ($_SERVER ['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * 是不是https请求.
     *
     * @return bool
     */
    public static function isHttps(): bool {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off';
    }

    /**
     * 是不是GET请求
     * @return bool
     */
    public static function isGet(): bool {
        return strtoupper($_SERVER ['REQUEST_METHOD']) == 'GET';
    }

    /**
     * 是不是POST请求.
     *
     * @return bool
     */
    public static function isPost(): bool {
        return strtoupper($_SERVER ['REQUEST_METHOD']) == 'POST';
    }

    /**
     * POST过来的数据的格式.
     *
     * @return string
     */
    public static function contentType(): string {
        //检测请求头
        $contentType = '';
        // Look for the content type header
        if (isset ($_SERVER ["HTTP_CONTENT_TYPE"])) {
            $contentType = $_SERVER ["HTTP_CONTENT_TYPE"];
        } else if (isset ($_SERVER ["CONTENT_TYPE"])) {
            $contentType = $_SERVER ["CONTENT_TYPE"];
        }

        return $contentType;
    }

    /**
     * 获取客户端传过来的值无论是通过GET方式还是POST方式.
     *
     * @param string  $name
     * @param mixed   $default
     * @param boolean $xss_clean 是否进行xss过滤
     *
     * @return mixed
     */
    public function get(string $name, $default = '', $xss_clean = true) {
        if ($xss_clean) {
            $ary = isset ($this->userData [ $name ]) ? $this->userData : (isset ($_REQUEST [ $name ]) ? $_REQUEST : []);
        } else {
            $ary = isset ($this->userData [ $name ]) ? $this->userData : (isset ($this->requestData [ $name ]) ? $this->requestData : []);
        }

        if (!$ary && strpos($name, '.') > 0) {
            $name = explode('.', $name);
            if (isset($this->arrayData[ $name[0] ])) {
                $ary = $this->arrayData[ $name[0] ];
            } else {
                $ary = $this->arrayData[ $name[0] ] = $this->get($name[0], [], $xss_clean);
            }
            $name = $name[1];
        }

        if (!isset ($ary [ $name ])) {

            return $default;
        }

        return $ary [ $name ];
    }

    /**
     * 获取所有请求数据.
     *
     * @param bool $xss_clean
     *
     * @return array
     */
    public function requests(bool $xss_clean = true): array {
        return array_merge($xss_clean ? $_REQUEST : $this->requestData, $this->userData);
    }

    /**
     * 添加用户数据供下次使用.
     *
     * @param array $data
     * @param bool  $reset
     * @param bool  $override 是否重写原请求参数
     */
    public function addUserData(array $data = [], bool $reset = false, bool $override = false) {
        if ($data && is_array($data)) {
            if ($override) {
                $data = array_diff_key($data, $_REQUEST);
            }
            if ($reset) {
                $this->userData = $data;
            } else {
                $this->userData = array_merge($this->userData, $data);
            }
        }
    }

    /**
     * 从php://input读取json格式的数据并添加到请求中.
     */
    private function addJsonPostBody() {
        // Look for the content type header
        if (isset ($_SERVER ["HTTP_CONTENT_TYPE"])) {
            $contentType = explode(';', $_SERVER ["HTTP_CONTENT_TYPE"])[0];
        } else if (isset ($_SERVER ["CONTENT_TYPE"])) {
            $contentType = explode(';', $_SERVER ["CONTENT_TYPE"])[0];
        } else {
            $contentType = null;
        }

        if ($contentType == 'application/json') {
            $postData = @file_get_contents('php://input');
            if ($postData) {
                $postData = @json_decode($postData, true);
                if ($postData) {
                    $this->addUserData($postData);
                }
            }
        } else if ($contentType == 'text/plain') {
            $this->addUserData(['rawData' => @file_get_contents('php://input')]);
        }
    }

    /**
     * 运行时添加的用户数据.
     *
     * @return array
     */
    public function getUserData(): array {
        return $this->userData;
    }

    /**
     * IP
     * @return string
     */
    public static function getIp(): string {
        static $cip = null;
        if ($cip === null) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
                $cips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $cip  = trim($cips[0]);
            } else if (isset($_SERVER ['HTTP_X_REAL_IP']) && $_SERVER ['HTTP_X_REAL_IP']) {
                $cip = $_SERVER ['HTTP_X_REAL_IP'];
            } else if (!empty ($_SERVER ['REMOTE_ADDR'])) {
                $cip = $_SERVER ['REMOTE_ADDR'];
            } else {
                $cip = '127.0.0.1';
            }
        }

        return $cip;
    }

    /**
     * 设置uuid
     */
    public static function setUUID() {
        if (isset ($_COOKIE ['__m_uuid'])) {
            self::$UUID = $_COOKIE ['__m_uuid'];

            return;
        }
        self::$UUID = uniqid();
        // 2 years = 63072000
        @setcookie('__m_uuid', self::$UUID, time() + 63072000, '/', '', false, true);
    }

    /**
     * 获取用户唯一ID
     *
     * @return string
     */
    public static function getUUID(): string {
        if (self::$UUID) {
            return self::$UUID;
        }
        if (isset ($_COOKIE ['__m_uuid'])) {
            self::$UUID = $_COOKIE ['__m_uuid'];
        } else {
            self::$UUID = uniqid();
            // 2 years = 63072000
            @setcookie('__m_uuid', self::$UUID, time() + 63072000, '/', '', false, true);
        }

        return self::$UUID;
    }

    public function offsetExists($offset) {
        return isset ($_REQUEST [ $offset ]) || isset ($this->userData [ $offset ]);
    }

    public function offsetGet($offset) {
        return $this->get($offset, '', true);
    }

    public function offsetSet($offset, $value) {
        $this->userData [ $offset ] = $value;
    }

    public function offsetUnset($offset) {
        if (isset ($this->userData [ $offset ])) {
            unset ($this->userData [ $offset ]);
        }
    }

    // 处理全局输入
    private function sanitizeGlobals() {
        //原始数据
        $this->requestData = array_merge([], $_REQUEST);
        //以下为cleaned数据
        $_GET     = $this->cleanInputData($_GET);
        $_POST    = $this->cleanInputData($_POST);
        $_REQUEST = $this->cleanInputData($_REQUEST);
        unset ($_COOKIE ['$Version']);
        unset ($_COOKIE ['$Path']);
        unset ($_COOKIE ['$Domain']);
        $_COOKIE = $this->cleanInputData($_COOKIE);
    }

    /**
     * @param $str
     *
     * @return array|mixed|string
     */
    private function cleanInputData($str) {
        if (is_array($str)) {
            $new_array = [];
            foreach ($str as $key => $val) {
                $new_array [ $this->cleanInputKeys($key) ] = $this->cleanInputData($val);
            }

            return $new_array;
        }
        $str = self::$xss_cleaner->xss_clean($str);
        // Standardize newlines
        if (strpos($str, "\r") !== false) {
            $str = str_replace(["\r\n", "\r"], "\n", $str);
        }

        return $str;
    }

    private function cleanInputKeys($str) {
        if (!preg_match('/^[\.a-z0-9:_\-\/-\\\\*]+$/i', $str)) {
            log_warn('Disallowed Key Characters:' . $str, 'input');
            Response::respond(403,'Disallowed Key Characters:' . $str);
        }

        return $str;
    }
}
