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

	private $dispatchers = array();

	private $preDispatchers = array();

	private $postDispatchers = array();

	private $urlParsedInfo;
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
	 */
	public function route($uri = '') {
		$response = Response::getInstance();
		if ($uri == '/' || !$uri) {
			$uri = '/index.html';
		}
		$url   = trim(parse_url($uri, PHP_URL_PATH), '/');
		$query = parse_url($uri, PHP_URL_QUERY);
		$args  = array();
		if ($query) {
			parse_str($query, $args);
			$this->xssCleaner->xss_clean($args);
		}
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