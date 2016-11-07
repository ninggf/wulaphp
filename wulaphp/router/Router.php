<?php
namespace wulaphp\router;

use ci\XssCleaner;
use wulaphp\io\Response;

/**
 * 路由器.
 *
 * @author leo
 *
 */
class Router {

	private $xssCleaner;
	private $dispatchers     = array();
	private $preDispatchers  = array();
	private $postDispatchers = array();

	private $urlParsedInfo;
	private $requestURL;
	private $queryParams;
	/**
	 * @var Router
	 */
	private static $INSTANCE;

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
	 * 当前是否在请求$url页面.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is($url) {
		$r = self::getRouter();

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
	 */
	public function route($uri = '') {
		$response = Response::getInstance();
		if ($uri == '/' || !$uri) {
			$uri = '/index.html';
		}
		$url              = apply_filter('router\parse_url', trim(parse_url($uri, PHP_URL_PATH), '/'));
		$this->requestURL = $url;
		$query            = parse_url($uri, PHP_URL_QUERY);
		$args             = array();
		if ($query) {
			parse_str($query, $args);
			$this->xssCleaner->xss_clean($args);
		}
		$this->queryParams = $args;
		$view              = null;
		//预处理，读取缓存的好时机
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
						if ($view) {
							break;
						}
					}
				}
				if ($view) {
					break;
				}
			}
			// 分发后处理，缓存内容的好时机.
			foreach ($this->postDispatchers as $dispatchers) {
				foreach ($dispatchers as $d) {
					if ($d instanceof IURLPostDispatcher) {
						$view = $d->postDispatch($url, $this, $view);
					}
				}
			}
			if ($view) {
				$response->output($view);
			} else if (DEBUG == DEBUG_DEBUG) {
				trigger_error('no route for ' . $uri, E_USER_ERROR);
			} else {
				Response::respond(404);
			}
		}
		$response->close();
	}
}