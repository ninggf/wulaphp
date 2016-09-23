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

	private $modules;

	private $xssCleaner;

	private $dispatchers = array();

	private $preDispatchers = array();

	private $postDispatchers = array();

	private $urlParsedInfo;

	/**
	 * Router constructor.
	 *
	 * @param array $modules
	 *
	 * @filter register_dispatcher Router
	 */
	public function __construct($modules = null) {
		$this->modules    = $modules;
		$this->xssCleaner = new XssCleaner();
		$this->register(new DefaultDispatcher (), 100);
		fire('register_dispatcher', $this);
	}

	/**
	 * 检测系统BASE URL.
	 *
	 * @param boolean $full 是否返回全路径.
	 *
	 * @return string
	 */
	public static function detect($full = false) {
		$script_name  = $_SERVER ['SCRIPT_NAME'];
		$script_name  = trim(str_replace(WWWROOT, '', $script_name), '/');
		$script_names = explode('/', $script_name);
		array_pop($script_names);
		$base = '/';
		if (!empty ($script_names) && !is_file(WWWROOT . $script_name)) {
			$web_roots = explode('/', trim(str_replace(DS, '/', WWWROOT), '/'));
			$matchs    = array();
			$pos       = 0;
			foreach ($web_roots as $chunk) {
				if ($chunk == $script_names [ $pos ]) {
					$matchs [] = $chunk;
					$pos++;
				} else {
					$matchs = array();
					$pos    = 0;
				}
			}
			if ($pos > 0) {
				$base .= implode('/', $matchs) . '/';
			}
		}
		if ($full) {
			$host     = isset ($_SERVER ['HTTP_HOST']) ? $_SERVER ['HTTP_HOST'] : 'localhost';
			$protocol = isset ($_SERVER ['HTTPS']) ? 'https://' : 'http://';
			$base     = $protocol . $host . $base;
		}

		return $base;
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
				$view = $d->preDispatch($url, $this, $view);
			}
		}
		if ($view) {
			$response->output($view);
		} else {
			$this->urlParsedInfo = new UrlParsedInfo ($uri, $url, $args);
			foreach ($this->dispatchers as $dispatchers) {
				foreach ($dispatchers as $d) {
					$view = $d->dispatch($url, $this, $this->urlParsedInfo);
					if ($view) {
						break;
					}
				}
				if ($view) {
					break;
				}
			}
			foreach ($this->postDispatchers as $dispatchers) {
				foreach ($dispatchers as $d) {
					$view = $d->postDispatch($url, $this, $view);
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