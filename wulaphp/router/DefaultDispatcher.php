<?php

namespace wulaphp\router;

use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\SmartyView;
use wulaphp\mvc\view\View;
use wulaphp\util\ObjectCaller;

/**
 * 默认分发器.
 *
 * @author Leo Ning.
 *
 */
class DefaultDispatcher implements IURLDispatcher {

	/**
	 * 将url 分发到模块控制器里的action.
	 *
	 * @param string        $url
	 * @param Router        $router
	 * @param UrlParsedInfo $parsedInfo ;
	 *
	 * @return \wulaphp\mvc\view\View
	 * @throws \Exception
	 */
	public function dispatch($url, $router, $parsedInfo) {
		$controllers = explode('/', $url);
		$pms         = [];
		$len         = count($controllers);
		$module      = '';
		$prefix      = null;
		$action      = 'index';
		if ($len == 0) {
			return null;
		} else if ($len == 1 && !empty ($controllers [0])) {
			$module = $controllers [0];
			if ($module == 'index.html') {
				$module = 'home';//首页分发给Home模块的默认控制器:IndexController.
			} else if (App::checkUrlPrefix($module)) {
				$prefix    = $module;
				$namespace = App::checkUrlPrefix($prefix);
				$module    = App::id2dir($namespace);
			}
		} else if ($len == 2) {
			$module = $controllers [0];
			if (App::checkUrlPrefix($module)) {
				$prefix = $module;
				$module = $controllers[1];
			} else {
				$action = $controllers [1];
			}
		} else if ($len > 2) {
			$module = $controllers [0];
			if (App::checkUrlPrefix($module)) {
				$prefix = $module;
				$module = $controllers[1];
				$action = $controllers [2];
				$pms    = array_slice($controllers, 3);
			} else {
				$action = $controllers [1];
				$pms    = array_slice($controllers, 2);
			}
		}
		$module    = strtolower($module);
		$namespace = App::dir2id($module, true);
		//查找默认模块
		if (!$namespace) {
			// uri=prefix 需要查找module与重置$action为index
			if ($len == 1 && ($dir = App::checkUrlPrefix($module))) {
				$prefix    = $module;
				$namespace = $dir;
				$module    = App::id2dir($namespace);
				$action    = 'index';
			} else if ($prefix) {
				// uri = prefix/action，需要查找module且重置$action
				if ($action != 'index') {
					array_unshift($pms, $action);
				}
				$action    = $module;
				$namespace = App::checkUrlPrefix($prefix);
				$module    = App::id2dir($namespace);
			}
		}
		if ($namespace) {
			$mm = App::getModuleById($namespace);
			if (!$mm->enabled) {
				//模块不可用.
				return null;
			}
			$ckey = 'rt@' . $url;
			$app  = RtCache::get($ckey);
			if (!$app) {
				$app = $this->findApp($module, $action, $pms, $namespace);
				RtCache::add($ckey, $app);
			} else if (is_file($app[3])) {
				include $app[3];
			} else {
				$app = $this->findApp($module, $action, $pms, $namespace);
				if ($app) {
					RtCache::add($ckey, $app);
				}
			}
			if ($app) {
				list ($controllerClz, $action, $pms, , $controllerSlag, $actionSlag) = $app;
				if (in_array($action, ['beforerun', 'afterrun', 'geturlprefix'])) {
					RtCache::delete($ckey);

					return null;
				}
				try {
					$clz = new $controllerClz (App::getModule($namespace));

					if ($clz instanceof Controller && $clz->slag == $controllerSlag) {
						$cprefix = '';
						if (method_exists($clz->clzName, 'urlGroup')) {
							$tmpPrefix = ObjectCaller::callClzMethod($clz->clzName, 'urlGroup');
							$cprefix   = $tmpPrefix && isset($tmpPrefix[1]) ? $tmpPrefix[1] : '';
						}
						if (($cprefix || $prefix) && $cprefix != $prefix) {
							RtCache::delete($ckey);

							return null;
						}
						$rqMethod = strtolower($_SERVER ['REQUEST_METHOD']);
						$rm       = ucfirst($rqMethod);
						// 存在index_get,index_post,add_get add_post这新的方法.
						$md          = $action . $rm;
						$actionFound = false;
						if (method_exists($clz, $md)) {
							$action      = $md;
							$actionSlag  = $actionSlag . '-' . $rqMethod;
							$actionFound = true;
						} else if (!method_exists($clz, $action)) {
							array_unshift($pms, $actionSlag);
							$action     = 'index';
							$actionSlag = 'index';
						}
						if (!$actionFound) {
							$md = $action . $rm;
							if (method_exists($clz, $md)) {
								$action      = $md;
								$actionSlag  = $actionSlag . '-' . $rqMethod;
								$actionFound = true;
							} else if (method_exists($clz, $action)) {
								$actionFound = true;
							}
						}
						if ($actionFound) {
							$ref = $clz->reflectionObj;

							$method     = $ref->getMethod($action);
							$methodSlag = Router::addSlash($method->getName());
							if (!$method->isPublic() || $methodSlag != $actionSlag) {
								return null;
							}
							/* @var \ReflectionParameter[] $params */
							$params = $method->getParameters();
							if (count($params) < count($pms)) {
								return null;
							}
							$rtn = $clz->beforeRun($action, $method);
							//beforeRun可以返回view了
							if ($rtn instanceof View) {
								$this->prepareView($rtn, $module, $clz, $action);

								return $rtn;
							}
							$args = [];
							if ($params) {
								$idx = 0;
								foreach ($params as $p) {
									$name    = $p->getName();
									$def     = isset ($pms [ $idx ]) ? $pms [ $idx ] : ($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null);
									$value   = rqst($name, $def, true);
									$args [] = is_array($value) ? array_map(function ($v) {
										return is_array($v) ? $v : urldecode($v);
									}, $value) : urldecode($value);
									$idx++;
								}
							}
							$view = ObjectCaller::callObjMethod($clz, $action, $args);
							$view = $clz->afterRun($action, $view);
							if ($view !== null) {
								if (is_array($view)) {
									$view = new JsonView($view);
								} else if (!$view instanceof View) {
									$view = new SimpleView($view);
								}
							}
							$this->prepareView($view, $module, $clz, $action);

							return $view;
						}
					}
				} catch (\ReflectionException $e) {
					if (DEBUG == DEBUG_DEBUG) {
						throw $e;
					}
				}
			}
		}

		return null;
	}

	/**
	 * 查找app处理器.
	 *
	 * @param string $module
	 * @param string $action
	 * @param array  $params
	 * @param string $namespace
	 *
	 * @return array array ($controllerClz,$action,$params )
	 */
	protected function findApp($module, $action, $params, $namespace) {
		if (is_numeric($action)) {
			array_unshift($params, $action);
			$action = 'index';
		}
		if ($action != 'index') {
			// Action Controller 的 index方法
			$controllerClz   = str_replace('-', '', ucwords($action, '-')) . 'Controller';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, 'index', $action];
			// 默认controller的action方法
			$controllerClz   = 'IndexController';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, $action, 'index'];

			foreach ($files as $file) {
				list ($controller_file, $controllerClz, $action, $controller) = $file;
				if (is_file($controller_file)) {
					include $controller_file;
					if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
						if ($action == 'index' && count($params) > 0) {
							$action = array_shift($params);
						}

						return [
							$controllerClz,
							Router::removeSlash($action),
							$params,
							$controller_file,
							$controller,
							$action
						];
					}
				}
			}
		} else {
			// 默认Controller的index方法
			$controllerClz   = 'IndexController';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$controllerClz   = $namespace . '\controllers\\' . $controllerClz;
			if (is_file($controller_file)) {
				include $controller_file;
				if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
					return [$controllerClz, Router::removeSlash($action), $params, $controller_file, 'index', $action];
				}
			}
		}

		return null;
	}

	/**
	 * @param $view
	 * @param $module
	 * @param $clz
	 * @param $action
	 */
	private function prepareView(&$view, $module, $clz, $action) {
		if ($view instanceof SmartyView) {
			$tpl = $view->getTemplate();
			if ($tpl) {
				if ($tpl{0} == '~') {
					$tpl     = substr($tpl, 1);
					$tpls    = explode('/', $tpl);
					$tpls[0] = App::id2dir($tpls[0]);
					$tpl     = implode('/', $tpls);
					unset($tpls[0]);
				} else {
					$tpl = $module . '/views/' . $tpl;
				}
				$view->setTemplate($tpl);
			} else {
				$view->setTemplate($module . '/views/' . $clz->ctrName . '/' . $action);
			}
		}
	}

}