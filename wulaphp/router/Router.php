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
	private $queryParams   = [];//URL的请求参数

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
		$this->register(new DefaultDispatcher (), 0);
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
	 * @param string $uri
	 *
	 * @filter router\parse_url url
	 * @throws \Exception when no router
	 * @return mixed when run in cli-server return false for assets.
	 */
	public function route($uri = '') {
		$response = Response::getInstance();
		if ($uri == '/' || !$uri) {
			$uri = '/index.html';
		}
		//解析url
		$url              = apply_filter('router\parse_url', trim(parse_url($uri, PHP_URL_PATH), '/'));
		$this->requestURL = $url;
		//从原生的URL中解析出参数
		$query = parse_url($uri, PHP_URL_QUERY);
		$args  = [];
		if ($query) {
			parse_str($query, $args);
			$this->xssCleaner->xss_clean($args);
		}
		$this->queryParams = $args;
		$view              = null;
		fire('router\beforeDispatch', $this, $url);
		//预处理
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
			} else if (PHP_RUNTIME_NAME == 'cli-server' && is_file(WWWROOT . $url)) {
				return false;
			} else if (DEBUG < DEBUG_ERROR) {
				throw new \Exception(__('No route for %s', $uri));
			} else {
				Response::respond(404);
			}
		}
		$response->close();

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
}