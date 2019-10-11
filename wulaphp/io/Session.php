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
    private $expire = 0;

    /**
     * Session constructor.
     *
     * @param int|null $expire
     */
    public function __construct($expire = null) {
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
    public function start($session_id = null) {
        if ($this->session_id) {
            return $this->session_id;
        }
        $session_expire = $this->expire;
        $http_only      = true;
        @ini_set('session.use_cookies', 1);
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
        try {
            @session_name($session_name);
            if (!empty ($session_id)) {
                $this->session_id = $session_id;
                @session_id($session_id);
                @session_start();
            } else {
                @session_start();
                $this->session_id = session_id();
            }
        } catch (\Exception $e) {
            $msg = 'Cannot start session: ' . $e->getMessage();
            log_error($msg);

            return '';
        }

        return $this->session_id;
    }
}
