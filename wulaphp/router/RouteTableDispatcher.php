<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\router;

use wulaphp\app\App;

/**
 * 基于路由表的分发器.
 *
 * @package wulaphp\router
 */
class RouteTableDispatcher implements IURLDispatcher {
	public function dispatch($url, $router, $parsedInfo) {
		$controllers = explode('/', $url);
		$len         = count($controllers);
		if ($len < 2) {//无法路由
			return null;
		}
		$module    = array_shift($controllers);
		$module    = strtolower($module);
		$namespace = App::dir2id($module, true);
		if (!$namespace) {
			return null;
		}

		//加载路由表
		$rtable = MODULE_ROOT . $module . DS . 'route.php';
		if (is_file($rtable)) {
			$routes = @include $rtable;
			$uk     = implode('/', $controllers);
			if ($routes && isset($routes[ $uk ])) {
				//['template' => 'abc.tpl', 'expire' => 100, 'func' => '','Content-Type'=>'text/html','data'=>[]]
				$route = $routes[ $uk ];
				if (isset($route['template']) && $route['template']) {
					$expire = intval(aryget('expire', $route), 0);
					$func   = aryget('func', $route);
					$data   = isset($route['data']) ? (array)$route['data'] : [];
					if ($func && is_callable($func)) {
						$data = $func($data);
					}
					$data = is_array($data) ? $data : ['result' => $data];
					if ($expire > 0) {
						define('EXPIRE', $expire);
					}
					if (isset($route['Content-Type'])) {
						$headers['Content-Type'] = $route['Content-Type'];
					} else {
						$headers = ['Content-Type' => Router::mimeContentType($url)];
					}
					unset($routes);

					return template($route['template'], $data, $headers);
				}
			}
			unset($routes);
		}

		return null;
	}
}