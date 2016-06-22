<?php
namespace wulaphp\router;

use wulaphp\router\hook\RouterHookTrigger;
<<<<<<< HEAD
=======
use wulaphp\mvc\view\View;
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
use wulaphp\io\Response;

/**
 * 路由器.
 *
 * @author leo
 *
 */
class Router {

    private $modules;

<<<<<<< HEAD
=======
    private $url;

    private $uri;

    private $args = array ();

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    private $xssCleaner;

    private $dispatchers = array ();

    private $preDispatchers = array ();

    private $postDispatchers = array ();

<<<<<<< HEAD
    private $urlParsedInfo;

    public function __construct($modules = null) {
        $this->modules = $modules;
        $this->xssCleaner = new \ci\XssCleaner ();
        $this->register ( new DefaultDispatcher (), 100 );
=======
    public function __construct($modules = null) {
        $this->modules = $modules;
        $this->xssCleaner = new \ci\XssCleaner ();
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
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
<<<<<<< HEAD
        $url = trim ( parse_url ( $uri, PHP_URL_PATH ), '/' );
        $query = parse_url ( $uri, PHP_URL_QUERY );
        $args = array ();
        if ($query) {
            parse_str ( $query, $args );
            $this->xssCleaner->xss_clean ( $args );
=======
        $this->uri = $uri;
        $this->url = trim ( parse_url ( $uri, PHP_URL_PATH ), '/' );
        $args = parse_url ( $uri, PHP_URL_QUERY );
        if ($args) {
            parse_str ( $args, $this->args );
            $this->xssCleaner->xss_clean ( $this->args );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
        }
        $view = null;
        foreach ( $this->preDispatchers as $dispatchers ) {
            foreach ( $dispatchers as $d ) {
<<<<<<< HEAD
                $view = $d->preDispatch ( $url, $this, $view );
            }
        }
        if ($view) {
            $response->output ( $view );
        } else {
            $this->urlParsedInfo = new UrlParsedInfo ( $uri, $url, $args );
            foreach ( $this->dispatchers as $dispatchers ) {
                foreach ( $dispatchers as $d ) {
                    $view = $d->dispatch ( $url, $this, $this->urlParsedInfo );
=======
                $view = $d->preDispatch ( $this->url, $this, $view );
            }
        }
        if ($view instanceof View) {
            $response->output ( $view );
        } else {
            foreach ( $this->dispatchers as $dispatchers ) {
                foreach ( $dispatchers as $d ) {
                    $view = $d->dispatch ( $this->url, $this );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
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
<<<<<<< HEAD
                    $view = $d->postDispatch ( $url, $this, $view );
                }
            }
            if ($view) {
                $response->output ( $view );
            } else if (DEBUG == DEBUG_DEBUG) {
                trigger_error ( 'no route for ' . $uri, E_USER_ERROR );
            } else {
                Response::respond ( 404 );
=======
                    $view = $d->preDispatch ( $this->url, $this, $view );
                }
            }
            if ($view instanceof View) {
                $response->output ( $view );
                log_error ( 'no one can handl it' );
            } else {
                log_error ( 'no one can handl it' );
                echo '404';
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
            }
        }
        $response->close ();
    }
}