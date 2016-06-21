<?php
namespace wulaphp\router;

use wulaphp\router\hook\RouterHookTrigger;
use wulaphp\mvc\view\View;
use wulaphp\io\Response;

/**
 * 路由器.
 *
 * @author leo
 *
 */
class Router {

    private $modules;

    private $url;

    private $uri;

    private $args = array ();

    private $xssCleaner;

    private $dispatchers = array ();

    private $preDispatchers = array ();

    private $postDispatchers = array ();

    public function __construct($modules = null) {
        $this->modules = $modules;
        $this->xssCleaner = new \ci\XssCleaner ();
        $trigger = new RouterHookTrigger ();
        $trigger->register ( $this );
    }

    /**
     * 检测系统BASE URL.
     *
     * @return string
     */
    public static function detect($full = false) {
        $script_name = $_SERVER ['SCRIPT_NAME'];
        $script_name = trim ( str_replace ( WWWROOT, '', $script_name ), '/' );
        $script_names = explode ( '/', $script_name );
        array_pop ( $script_names );
        $base = '/';
        if (! empty ( $script_names ) && ! is_file ( WWWROOT . $script_name )) {
            $web_roots = explode ( '/', trim ( str_replace ( DS, '/', WWWROOT ), '/' ) );
            $matchs = array ();
            $pos = 0;
            foreach ( $web_roots as $chunk ) {
                if ($chunk == $script_names [$pos]) {
                    $matchs [] = $chunk;
                    $pos ++;
                } else {
                    $matchs = array ();
                    $pos = 0;
                }
            }
            if ($pos > 0) {
                $base .= implode ( '/', $matchs ) . '/';
            }
        }
        if ($full) {
            $host = isset ( $_SERVER ['HTTP_HOST'] ) ? $_SERVER ['HTTP_HOST'] : 'localhost';
            $protocol = isset ( $_SERVER ['HTTPS'] ) ? 'https://' : 'http://';
            $base = $protocol . $host . $base;
        }
        return $base;
    }

    /**
     * 注册分发器.
     *
     * @param IURLDispatcher $dispatcher
     * @param number $index
     */
    public function register(IURLDispatcher $dispatcher, $index = 10) {
        $this->dispatchers [$index] [] = $dispatcher;
        ksort ( $this->dispatchers, SORT_NUMERIC );
    }

    /**
     * 注册前置分发器.
     *
     * @param IURLPreDispatcher $dispatcher
     * @param number $index
     */
    public function registerPreDispatcher(IURLPreDispatcher $dispatcher, $index = 10) {
        $this->preDispatchers [$index] [] = $dispatcher;
        ksort ( $this->preDispatchers, SORT_NUMERIC );
    }

    /**
     * 注册后置分发器.
     *
     * @param IURLPostDispatcher $dispatcher
     * @param number $index
     */
    public function registerPostDispatcher(IURLPostDispatcher $dispatcher, $index = 10) {
        $this->postDispatchers [$index] [] = $dispatcher;
        ksort ( $this->postDispatchers, SORT_NUMERIC );
    }

    /**
     * 将URL解析后分发给分发器处理.
     *
     * @param string $url
     */
    public function route($uri = '') {
        $response = Response::getInstance ();
        if ($uri == '/' || ! $uri) {
            $uri = '/index.html';
        }
        $this->uri = $uri;
        $this->url = trim ( parse_url ( $uri, PHP_URL_PATH ), '/' );
        $args = parse_url ( $uri, PHP_URL_QUERY );
        if ($args) {
            parse_str ( $args, $this->args );
            $this->xssCleaner->xss_clean ( $this->args );
        }
        $view = null;
        foreach ( $this->preDispatchers as $dispatchers ) {
            foreach ( $dispatchers as $d ) {
                $view = $d->preDispatch ( $this->url, $this, $view );
            }
        }
        if ($view instanceof View) {
            $response->output ( $view );
        } else {
            foreach ( $this->dispatchers as $dispatchers ) {
                foreach ( $dispatchers as $d ) {
                    $view = $d->dispatch ( $this->url, $this );
                    if ($view) {
                        break;
                    }
                }
                if ($view) {
                    break;
                }
            }
            foreach ( $this->postDispatchers as $dispatchers ) {
                foreach ( $dispatchers as $d ) {
                    $view = $d->preDispatch ( $this->url, $this, $view );
                }
            }
            if ($view instanceof View) {
                $response->output ( $view );
                log_error ( 'no one can handl it' );
            } else {
                log_error ( 'no one can handl it' );
                echo '404';
            }
        }
        $response->close ();
    }
}