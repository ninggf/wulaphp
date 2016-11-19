<?php
namespace wulaphp\router;

use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\view\SmartyView;
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
	 */
	public function dispatch($url, $router, $parsedInfo) {
		$controllers = explode('/', $url);
		$pms         = array();
		$len         = count($controllers);
		$module      = '';
		$prefix      = null;
		$action      = 'index';
		if ($len == 0) {
			return null;
		} else if ($len == 1 && !empty ($controllers [0])) {
			$module = $controllers [0];
			if ($module == 'index.html') {
				$module = 'home';//首页分发给Home模块的默认控制器HomeController.
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
		if ($namespace) {
			$app = RtCache::get($url);
			if (!$app) {
				$app = $this->findApp($module, $action, $pms, $namespace);
				RtCache::add($url, $app);
			} else {
				if (is_file($app[3])) {
					include $app[3];
				} else {
					$app = $this->findApp($module, $action, $pms, $namespace);
					RtCache::add($url, $app);
				}
			}
			if ($app) {
				list ($controllerClz, $action, $pms) = $app;
				if (in_array($action, ['beforerun', 'afterrun', 'geturlprefix'])) {
					RtCache::delete($url);

					return null;
				}
				try {
					$clz = new $controllerClz (App::getModule($namespace));
					if ($clz instanceof Controller) {
						$cprefix = '';
						if (method_exists($clz->clzName, 'urlGroup')) {
							$tmpPrefix = ObjectCaller::callClzMethod($clz->clzName, 'urlGroup');
							$cprefix   = $tmpPrefix && isset($tmpPrefix[1]) ? $tmpPrefix[1] : '';
						}
						if (($cprefix || $prefix) && $cprefix != $prefix) {
							RtCache::delete($url);

							return null;
						}
						$rm = ucfirst(strtolower($_SERVER ['REQUEST_METHOD']));
						// 存在index_get,index_post,add_get add_post这新的方法.
						$md          = $action . $rm;
						$actionFound = false;
						if (method_exists($clz, $md)) {
							$action      = $md;
							$actionFound = true;
						} else if (!method_exists($clz, $action)) {
							array_unshift($pms, $action);
							$action = 'index';
						}
						$md = $action . $rm;
						if (method_exists($clz, $md)) {
							$action      = $md;
							$actionFound = true;
						} else if (method_exists($clz, $action)) {
							$actionFound = true;
						}

						if ($actionFound) {
							$ref    = $clz->reflectionObj;
							$method = $ref->getMethod($action);
							if (!$method->isPublic()) {
								return null;
							}
							$params = $method->getParameters();
							if (count($params) < count($pms)) {
								if (DEBUG == DEBUG_DEBUG) {
									trigger_error('the count of parameters of "' . $controllerClz . '::' . $action . '" does not match, except ' . count($params) . ' but ' . count($pms) . ' given.', E_USER_ERROR);
								} else {
									return null;
								}
							}
							$clz->beforeRun($action, $method);
							$args = array();
							if ($params) {
								$idx = 0;
								foreach ($params as $p) {
									$name    = $p->getName();
									$def     = isset ($pms [ $idx ]) ? $pms [ $idx ] : ($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null);
									$value   = rqst($name, $def, true);
									$args [] = urldecode($value);
									$idx++;
								}
							}
							$view = ObjectCaller::callObjMethod($clz, $action, $args);
							$view = $clz->afterRun($action, $view);
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
								} elseif ($namespace == $clz->ctrName) {
									$view->setTemplate($module . '/views/' . $action);
								} else {
									$view->setTemplate($module . '/views/' . $clz->ctrName . '/' . $action);
								}
							}

							return $view;
						}
					}
				} catch (\ReflectionException $e) {
					if (DEBUG == DEBUG_DEBUG) {
						trigger_error(var_export($e, true), E_USER_ERROR);
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
			$controllerClz   = ucwords($action) . 'Controller';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$files []        = array($controller_file, $namespace . '\controllers\\' . $controllerClz, 'index');
			// 默认controller的action方法
			$controllerClz   = ucfirst($namespace) . 'Controller';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$files []        = array($controller_file, $namespace . '\controllers\\' . $controllerClz, $action);

			foreach ($files as $file) {
				list ($controller_file, $controllerClz, $action) = $file;
				if (is_file($controller_file)) {
					include $controller_file;
					if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
						if ($action == 'index' && count($params) > 0) {
							$action = array_shift($params);
						}

						return array($controllerClz, $action, $params, $controller_file);
					}
				}
			}
		} else {
			// 默认Controller的index方法
			$controllerClz   = ucwords($module) . 'Controller';
			$controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
			$controllerClz   = $namespace . '\controllers\\' . $controllerClz;
			if (is_file($controller_file)) {
				include $controller_file;
				if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
					return array($controllerClz, $action, $params, $controller_file);
				}
			}
		}

		return null;
	}
}