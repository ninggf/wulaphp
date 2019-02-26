<?php

namespace wulaphp\io;

use wulaphp\app\App;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\View;

/**
 *
 * @author  Windywany
 * @package io
 * @date    12-9-16 下午5:53
 *          $Id$
 */
class Response {
    private        $before_out = null;
    private        $content    = '';
    private        $view       = null;
    private static $INSTANCE   = null;

    private $bufferLevel = 0;
    private $bufferName;

    /**
     * 初始化.
     */
    public function __construct() {
        if (self::$INSTANCE == null) {
            @header_remove('X-Powered-By');
            if ($obl = @ob_get_level()) {
                $this->before_out = @ob_get_clean();
            }
            if (@ob_start(function ($content) {
                $this->content = apply_filter('filter_output_content', $content);
                if ($this->before_out) {
                    if (DEBUG == DEBUG_DEBUG) {
                        return '<!--' . $this->before_out . '-->' . $this->content;
                    } else {
                        log_warn($this->before_out, 'bootstrap');
                    }
                }

                return $this->content;
            })) {
                $status            = ob_get_status();
                $this->bufferLevel = $status['level'];
                $this->bufferName  = $status['name'];
            } else {
                !trigger_error('cannot open response', E_USER_ERROR) or exit(1);
            }
        }
        self::$INSTANCE = $this;
    }

    /**
     * 得到全局唯一Response实例.
     *
     * @return Response
     */
    public static function getInstance() {
        if (self::$INSTANCE == null) {
            new Response ();
        }

        return self::$INSTANCE;
    }

    /**
     * set response view instance.
     *
     * @param View $view
     */
    public function setView($view) {
        if ($view instanceof View) {
            $this->view = $view;
        }
    }

    /**
     * 禁用浏览器缓存.
     */
    public static function nocache() {
        $headers = [
            'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma'        => 'no-cache'
        ];
        foreach ($headers as $header => $val) {
            @header($header . ': ' . $val);
        }
    }

    /**
     * 输出缓存头.
     *
     * @param int|null    $last_modify
     * @param int         $expire
     * @param string|null $etag
     */
    public static function out_cache_header($last_modify = null, $expire = 3600, $etag = null) {
        $last_modify = $last_modify == null ? time() : $last_modify;
        @header('Pragma: cache');
        @header('Cache-Control: public, must-revalidate,  max-age=' . $expire, true);
        @header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modify) . ' GMT', true);
        @header('Expires: ' . gmdate('D, d M Y H:i:s', $last_modify + $expire) . ' GMT', true);
        if ($etag) {
            @header('Etag: ' . $etag);
        }
    }

    /**
     * 缓存头版本2.
     *
     * @param int      $expire
     * @param int|null $last_modify
     */
    public static function cache($expire = 3600, $last_modify = null) {
        $time    = time();
        $date    = gmdate('D, d M Y H:i:s', $time) . ' GMT';
        $ldate   = $last_modify ? gmdate('D, d M Y H:i:s', $last_modify) . ' GMT' : $date;
        $headers = [
            'Age'           => $expire,
            'Date'          => $date,
            'Expires'       => gmdate('D, d M Y H:i:s', $time + $expire) . ' GMT',
            'Last-Modified' => $ldate,
            'Cache-Control' => 'public, must-revalidate, max-age=' . $expire,
            'Pragma'        => 'cache'
        ];

        foreach ($headers as $header => $val) {
            @header($header . ': ' . $val);
        }
    }

    /**
     * 设置最后修改日期.
     *
     * @param int $last_modify
     */
    public static function lastModified($last_modify) {
        $time    = time();
        $date    = gmdate('D, d M Y H:i:s', $time) . ' GMT';
        $ldate   = gmdate('D, d M Y H:i:s', $last_modify) . ' GMT';
        $headers = [
            'Date'          => $date,
            'Last-Modified' => $ldate
        ];

        foreach ($headers as $header => $val) {
            @header($header . ': ' . $val);
        }
    }

    /**
     * 跳转.
     *
     * @param string       $location 要转到的网址
     * @param string|array $args     参数
     * @param int          $status   响应代码
     */
    public static function redirect($location, $args = "", $status = 302) {
        global $is_IIS;
        if (!$location) {
            return;
        }
        if (!empty ($args) && is_array($args)) {
            $_args = [];
            foreach ($args as $n => $v) {
                $_args [ $n ] = $n . '=' . urlencode($v);
            }
            $args = implode('&', $_args);
        }
        if (!empty ($args) && is_string($args)) {
            if (strpos($location, '?') !== false) {
                $location .= '&' . $args;
            } else {
                $location .= '?' . $args;
            }
        }

        if ($is_IIS) {
            @header("Refresh: 0;url=$location");
        } else {
            if (php_sapi_name() != 'cgi-fcgi') {
                status_header($status); // This causes problems on IIS and some
            }
            @header("Location: $location", true, $status);
        }
        exit ();
    }

    /**
     * 错误提示.
     *
     * @param string $message
     */
    public static function error($message) {
        self::respond(400, $message);
    }

    /**
     * 响应对应的状态码.
     *
     * @param int          $status respond status code.
     * @param string|array $message
     */
    public static function respond($status = 404, $message = '') {
        http_response_code($status);
        if (Request::isAjaxRequest()) {
            @header('ajax: 1');
            Response::getInstance()->output(['message' => $message ? $message : __('error occurred')]);
            Response::getInstance()->close();
        } else {
            if ($status == 404) {
                $data ['message'] = $message;
                $view             = template('404.tpl', $data);
            } else if ($status == 403) {
                $data ['message'] = $message;
                $view             = template('403.tpl', $data);
            } else if ($status == 500) {
                $data ['message'] = $message;
                $view             = template('500.tpl', $data);
            } else if ($status == 503) {
                $data ['message'] = $message;
                $view             = template('503.tpl', $data);
            } else if ($message) {
                if (is_array($message)) {
                    $view = new JsonView($message);
                } else {
                    $view = new SimpleView($message);
                }
            } else {
                $view = null;
            }
            if ($view) {
                Response::getInstance()->output($view);
                Response::getInstance()->close();
            }
        }
        exit (1);
    }

    /**
     * 设置cookie.
     *
     * @param string      $name 变量名
     * @param null|mixed  $value
     * @param null|int    $expire
     * @param null|string $path
     * @param null|string $domain
     * @param null|bool   $security
     */
    public static function cookie($name, $value = null, $expire = null, $path = null, $domain = null, $security = null) {
        $settings       = App::cfg();
        $cookie_setting = array_merge2([
            'expire'   => 0,
            'path'     => '/',
            'domain'   => '',
            'security' => false
        ], $settings->get('cookie', []));
        if ($expire == null) {
            $expire = intval($cookie_setting ['expire']);
        }
        if ($path == null) {
            $path = $cookie_setting ['path'];
        }
        if ($domain == null) {
            $domain = $cookie_setting ['domain'];
        }
        if ($security == null) {
            $security = $cookie_setting ['security'];
        }
        if ($expire != 0) {
            $expire = time() + $expire;
        }
        @setcookie($name, $value, $expire, $path, $domain, $security);
    }

    /**
     * 输出view产品的内容.
     *
     * @param View $view
     * @param bool $return
     *
     * @filter before_output_content $content
     * @return string|null
     */
    public function output($view = null, $return = false) {
        if ($view instanceof View) {
            $this->view = $view;
        } else if (is_string($view) || is_bool($view) || is_numeric($view)) {
            $this->view = new SimpleView ($view);
        } else if (is_array($view)) {
            $this->view = new JsonView ($view);
        }

        if ($this->view instanceof View) {
            if ($return) {
                $content = $this->view->render();

                return $content;
            } else {
                $this->view->echoHeader();
                $content = $this->view->render();
                if (is_string($content) && $content) {
                    $content = apply_filter('before_output_content', $content, $this->view);
                    echo str_replace('<!-- benchmark -->', (microtime(true) - WULA_STARTTIME), $content);
                }
            }
        } else {
            Response::respond(404);
        }

        return null;
    }

    /**
     * 关闭响应，将内容输出的浏览器，同时触发after_content_output勾子.
     *
     * @param bool $exit
     *
     * @fire after_content_output $content
     */
    public function close($exit = true) {
        $buffer = @ob_get_status();
        if ($buffer && $buffer['type'] && $buffer['name'] == $this->bufferName && $buffer['level'] == $this->bufferLevel) {
            @ob_end_flush();
        }

        self::$INSTANCE = null;
        if ($exit) {
            exit ();
        } else {
            fire('after_content_output', $this->content);
        }
    }
}
// END OF FILE response.php