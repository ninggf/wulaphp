<?php
namespace wulaphp\io;
// use cookie for session id
class Session {

    private $session_id;

    private $expire = 900;

    public function __construct($expire = 900) {
        $this->expire = intval ( $expire );
    }

    /**
     * start the session
     */
    public function start() {
        if ($this->session_id) {
            return $this->session_id;
        }
        $session_expire = $this->expire;
        $http_only = true;
        @ini_set ( 'session.use_cookies', 1 );
        @session_cache_limiter ( 'private' );
        @session_set_cookie_params ( $session_expire, '/', '', false, $http_only );
        @session_cache_expire ( ceil ( $session_expire / 60 ) );
        $session_path = '';
        if (! empty ( $session_path ) && is_dir ( $session_path )) {
            @session_save_path ( $session_path );
        }
        $session_name = get_session_name ();
        $session_id = isset ( $_COOKIE [$session_name] ) ? $_COOKIE [$session_name] : null;
        if (empty ( $session_id ) && isset ( $_REQUEST [$session_name] )) {
            $session_id = $_REQUEST [$session_name];
        }
        @session_name ( $session_name );
        if (! empty ( $session_id )) {
            @session_id ( $session_id );
        }
        @session_start ();
        $this->session_id = session_id ();
        return $this->session_id;
    }
}
?>