<?php

namespace wulaphp\io;

use wulaphp\app\App;

/**
 * 会话类.
 *
 * @package wulaphp\io
 */
class Session {
    private $session_id;
    private $expire;

    /**
     * Session constructor.
     *
     * @param int|null $expire
     */
    public function __construct(?int $expire = null) {
        if (is_null($expire)) {
            $this->expire = App::icfg('expire', 0);
        } else {
            $this->expire = intval($expire);
        }
    }

    /**
     * start the session
     *
     * @param string $session_id
     *
     * @return string session_id
     */
    public function start(?string $session_id = null) {
        if ($this->session_id) {
            return $this->session_id;
        }

        try {
            $save_handler   = @ini_get('session.save_handler');
            $session_expire = $this->expire;
            $http_only      = true;
            @ini_set('session.use_cookies', 1);
            @ini_set('session.cookie_httponly', 1);
            @session_set_cookie_params($session_expire, '/', '', false, $http_only);
            if ($session_expire) {
                @ini_set('session.gc_maxlifetime', $session_expire + 2);
            }
            $session_name = get_session_name();
            if (empty($session_id)) {
                $session_id = isset ($_COOKIE [ $session_name ]) ? $_COOKIE [ $session_name ] : null;
                if (empty ($session_id) && isset ($_REQUEST [ $session_name ])) {
                    $session_id = $_REQUEST [ $session_name ];
                }
            }
            if ($save_handler == 'redis') {
                ini_set('redis.session.locking_enabled', 1);//启用锁
                ini_set('redis.session.lock_expire', 120);//锁超时2分钟
                ini_set('redis.session.lock_retries', - 1);//无限次重试
                ini_set('redis.session.lock_wait_time', 2);// 每隔多久重试一次
                if (!$session_expire) {
                    ini_set('session.gc_maxlifetime', 43200);//12个小时
                }
            }
            @session_name($session_name);
            ob_start();
            if (!empty ($session_id)) {
                session_id($session_id);
                if (session_start()) {
                    $this->session_id = $session_id;
                } else {
                    ob_get_clean();
                    throw new \Exception('"' . $save_handler . '" save handler does not work');
                }
            } else {
                if (session_start()) {
                    $this->session_id = session_id();
                } else {
                    ob_get_clean();
                    throw new \Exception('"' . $save_handler . '" save handler does not work');
                }
            }
            ob_end_clean();
        } catch (\Exception $e) {
            $msg = 'Cannot start session: ' . $e->getMessage();
            if (DEBUG < DEBUG_ERROR) {
                show_exception_page($e);
            } else {
                log_error($msg);
            }

            return '';
        }

        return $this->session_id;
    }

    /**
     * 换session，可以实现登录前一个session，登录后一个session。
     * @return string
     */
    public function changeId() {
        if ($this->session_id) {
            session_unset();
            session_regenerate_id(true);
            $this->session_id = session_id();
        }

        return $this->session_id;
    }

    /**
     * 销毁session。
     */
    public function destory() {
        if ($this->session_id) {
            session_destroy();
            $this->session_id = null;
        }
    }

    /**
     * 关闭session。
     */
    public function close() {
        if ($this->session_id) {
            session_write_close();
            $this->session_id = null;
        }
    }
}
