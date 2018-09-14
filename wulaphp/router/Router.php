<?php

namespace wulaphp\router;

use ci\XssCleaner;
use wulaphp\io\Response;

/**
 * 路由器.负载解析URL并将URL分发给相应的处理器.
 *
 * @author  leo
 * @sine    1.0.0
 */
class Router {
    private $xssCleaner;
    private $dispatchers     = [];//分发器列表
    private $preDispatchers  = [];//前置分发器列表
    private $postDispatchers = [];//后置分发器列表

    private $urlParsedInfo = null;//解析的URL数据.
    private $requestURL    = null;//解析后的URL
    private $queryParams   = [];//QueryString 请求参数
    public  $urlParams     = [];//URL中的参数
    public  $requestURI;//请求URI
    /**
     * @var Router
     */
    private static $INSTANCE;//路由器唯一实例.

    /**
     * Router constructor.
     *
     *
     * @filter router\registerDispatcher Router
     */
    private function __construct() {
        $this->xssCleaner = new XssCleaner();
        $dd               = new DefaultDispatcher ();
        $this->register($dd, 0);
        //默认模块分发器
        if (defined('DEFAULT_MODULE') && DEFAULT_MODULE) {
            $this->register(new DefaultModuleDispatcher($dd, DEFAULT_MODULE), 1);
        }
        //路由表分发器
        $this->register(new RouteTableDispatcher (), 2);
        fire('router\registerDispatcher', $this);
    }

    /**
     * 获取路由器实例.
     * @return \wulaphp\router\Router
     */
    public static function getRouter() {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new Router();
        }

        return self::$INSTANCE;
    }

    /**
     * 获取本次请求的URI.
     *
     * @return string|null
     */
    public static function getURI() {
        if (isset($_SERVER ['REQUEST_URI'])) {
            if (WWWROOT_DIR != '/') {
                $uri = substr($_SERVER ['REQUEST_URI'], strlen(WWWROOT_DIR) - 1);
            } else {
                $uri = $_SERVER ['REQUEST_URI'];
            }

            return $uri;
        }

        return null;
    }

    /**
     * 获取本次请求的全URI
     * @return null|string
     */
    public static function getFullURI() {
        if (isset($_SERVER ['REQUEST_URI'])) {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') {
                $schema = 'https://';
            } else {
                $schema = 'http://';
            }
            $uri = $schema . $_SERVER['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];

            return $uri;
        }

        return null;
    }

    /**
     * 当前是否在请求$url页面.
     *
     * @param string $url
     * @param bool   $regexp
     *
     * @return bool
     */
    public static function is($url, $regexp = false) {
        $r = self::getRouter();
        if ($regexp) {
            return preg_match('`^' . $url . '$`', $r->requestURL);
        }

        return $url == $r->requestURL;
    }

    /**
     * 请求是否与正则匹配.
     *
     * @param string $pattern 测试正则表达式.
     *
     * @return bool|array 匹配结果.
     */
    public static function match($pattern) {
        $r = self::getRouter();
        if (preg_match($pattern, $r->requestURL, $ms)) {
            return $ms;
        }

        return false;
    }

    /**
     * 解析后的URL信息.
     *
     * @return \wulaphp\router\UrlParsedInfo
     */
    public function getParsedInfo() {
        return $this->urlParsedInfo;
    }

    /**
     * 取URL中的位置参数
     *
     * @param int    $pos
     * @param string $default
     *
     * @return mixed|string
     */
    public function getParam($pos = 0, $default = '') {
        $pos = intval($pos);

        return isset($this->urlParams[ $pos ]) ? $this->urlParams[ $pos ] : $default;
    }

    /**
     * 注册分发器.
     *
     * @param IURLDispatcher $dispatcher
     * @param int            $index
     */
    public function register(IURLDispatcher $dispatcher, $index = 10) {
        $this->dispatchers [ $index ] [] = $dispatcher;
        ksort($this->dispatchers, SORT_NUMERIC);
    }

    /**
     * 注册前置分发器.
     *
     * @param IURLPreDispatcher $dispatcher
     * @param int               $index
     */
    public function registerPreDispatcher(IURLPreDispatcher $dispatcher, $index = 10) {
        $this->preDispatchers [ $index ] [] = $dispatcher;
        ksort($this->preDispatchers, SORT_NUMERIC);
    }

    /**
     * 注册后置分发器.
     *
     * @param IURLPostDispatcher $dispatcher
     * @param int                $index
     */
    public function registerPostDispatcher(IURLPostDispatcher $dispatcher, $index = 10) {
        $this->postDispatchers [ $index ] [] = $dispatcher;
        ksort($this->postDispatchers, SORT_NUMERIC);
    }

    /**
     * 将URL解析后分发给分发器处理.
     *
     * @filter router\parse_url url
     * @throws \Exception when no router
     * @return mixed when run in cli-server return false for assets.
     */
    public function route() {
        static $alias = false;
        $response = Response::getInstance();
        $uri      = self::getURI();
        if ($uri == '/' || !$uri) {
            $uri = '/index.html';
        }
        //解析url
        $this->urlParams  = [];
        $this->requestURI = parse_url($uri, PHP_URL_PATH);
        //从原生的URL中解析出参数
        $query = @parse_url($uri, PHP_URL_QUERY);
        $args  = [];
        if ($query) {
            parse_str($query, $args);
            $this->xssCleaner->xss_clean($args);
        }
        $this->queryParams = $args;
        $url               = apply_filter('router\parse_url', trim($this->requestURI, '/'));
        $this->requestURL  = $url;
        if (defined('ALIAS_ENABLED') && ALIAS_ENABLED) {
            if ($alias === false) {
                $aliasFile = MODULES_PATH . 'alias.php';
                if (is_file($aliasFile)) {
                    $alias = (array)include $aliasFile;
                } else {
                    $alias = [];
                }
            }
            if ($url && isset($alias[ $url ]) && $alias[ $url ]) {
                $url = $alias[ $url ];
            }
        }
        fire('router\beforeDispatch', $this, $url);
        //预处理
        $view = null;
        foreach ($this->preDispatchers as $dispatchers) {
            foreach ($dispatchers as $d) {
                if ($d instanceof IURLPreDispatcher) {
                    $view = $d->preDispatch($url, $this, $view);
                }
            }
        }
        if ($view) {
            $response->output($view);
        } else {
            // 真正分发
            $this->urlParsedInfo = new UrlParsedInfo ($uri, $url, $args);
            foreach ($this->dispatchers as $dispatchers) {
                foreach ($dispatchers as $d) {
                    if ($d instanceof IURLDispatcher) {
                        $view = $d->dispatch($url, $this, $this->urlParsedInfo);
                        if (is_array($view) || $view) {
                            break;
                        }
                        $this->urlParsedInfo->reset();
                    }
                }
                if (is_array($view) || $view) {
                    break;
                }
            }

            foreach ($this->postDispatchers as $dispatchers) {
                foreach ($dispatchers as $d) {
                    if ($d instanceof IURLPostDispatcher) {
                        $view = $d->postDispatch($url, $this, $view);
                    }
                }
            }
            if (is_array($view) || $view) {
                $response->output($view);
            } else if (defined('DEBUG') && DEBUG < DEBUG_ERROR) {
                throw new \Exception(__('no route for %s', $uri));
            } else {
                Response::respond(404);
            }
        }
        $response->close(false);

        return false;
    }

    /**
     * 将abc-def-hig转换为abcDefHig.
     *
     * @param string $string
     *
     * @return string 转换后的字符.
     */
    public static function removeSlash($string) {
        return preg_replace_callback('/-([a-z])/', function ($ms) {
            return strtoupper($ms[1]);
        }, $string);
    }

    /**
     * 将AbcDefHig转换为abc-def-hig.
     *
     * @param string $string 要转换的字符.
     *
     * @return string 转换后的字符.
     */
    public static function addSlash($string) {
        $string = lcfirst($string);

        return preg_replace_callback('#[A-Z]#', function ($r) {
            return '-' . strtolower($r [0]);
        }, $string);
    }

    /**
     * 检测文件mime类型
     *
     * @param string $filename
     *
     * @return string
     */
    public static function mimeContentType($filename) {
        static $mime_types = [
            'txt'  => 'text/plain',
            'htm'  => 'text/html',
            'html' => 'text/html',
            'php'  => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
            'flv'  => 'video/x-flv',
            'png'  => 'image/png',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',
            'svg'  => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            'exe'  => 'application/x-msdownload',
            'msi'  => 'application/x-msdownload',
            'cab'  => 'application/vnd.ms-cab-compressed',
            'mp3'  => 'audio/mpeg',
            'qt'   => 'video/quicktime',
            'mov'  => 'video/quicktime',
            'pdf'  => 'application/pdf',
            'psd'  => 'image/vnd.adobe.photoshop',
            'ai'   => 'application/postscript',
            'eps'  => 'application/postscript',
            'ps'   => 'application/postscript',
            'doc'  => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/vnd.ms-excel',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet'
        ];
        $chks = explode('.', $filename);
        $ext  = strtolower(array_pop($chks));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[ $ext ];
        } else if (is_file($filename) && function_exists('finfo_open')) {
            $finfo    = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);

            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
}