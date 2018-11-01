<?php

namespace wulaphp\router;

use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\controller\SubModuleRouter;
use wulaphp\mvc\view\IModuleView;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
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
     * @param UrlParsedInfo $parsedInfo
     *
     * @return \wulaphp\mvc\view\View
     * @throws \Exception
     */
    public function dispatch($url, $router, $parsedInfo) {
        //检测请求是否合法
        $strict_mode = @constant('URL_STRICT_MODE');
        if (($strict_mode || is_null($strict_mode)) && $router->requestURI != '/' && substr($router->requestURI, -1, 1) == '/') {
            return null;
        }

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
                $app = self::findApp($module, $action, $pms, $namespace);
                RtCache::add($ckey, $app);
            } else if (is_file($app[3])) {
                include_once $app[3];
            } else {
                $app = self::findApp($module, $action, $pms, $namespace);
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
                        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']) {
                            $rqMethod = strtolower($_SERVER ['REQUEST_METHOD']);
                        } else {
                            $rqMethod = 'get';
                        }
                        $rm = ucfirst($rqMethod);
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
                            $ref        = $clz->reflectionObj;
                            $method     = $ref->getMethod($action);
                            $methodSlag = Router::addSlash($method->getName());
                            if (!$method->isPublic() || $methodSlag != $actionSlag) {
                                return null;
                            }
                            /* @var \ReflectionParameter[] $params */
                            $params      = $method->getParameters();
                            $paramsCount = count($params);// 方法参数个数
                            $argsCount   = count($pms);// 用户通过url传递过来的参数个数
                            if ($paramsCount < $argsCount) {
                                if ($paramsCount == 0 || ($paramsCount == 1 && !$params[0]->isVariadic())) {
                                    return null;
                                }
                            }
                            $rtn = $clz->beforeRun($action, $method);
                            //beforeRun可以返回view了
                            if ($rtn instanceof View) {
                                self::prepareView($rtn, $module, $clz, $action);

                                return $rtn;
                            }
                            $args    = [];
                            $aryArgs = false;//使用可变参数了
                            if ($paramsCount) {
                                if ($paramsCount == 1 && $params[0]->isVariadic()) {
                                    $args    = $pms;
                                    $aryArgs = true;
                                } else {
                                    $idx = 0;
                                    foreach ($params as $p) {
                                        $name    = $p->getName();
                                        $da      = $p->isDefaultValueAvailable();
                                        $def     = isset($pms [ $idx ]) ? $pms [ $idx ] : (($da ? $p->getDefaultValue() : null));
                                        $value   = rqst($name, $def, true);
                                        $args [] = is_array($value) ? array_map(function ($v) {
                                            return is_array($v) ? $v : urldecode($v);
                                        }, $value) : urldecode($value);
                                        $idx++;
                                    }
                                }
                            }
                            $router->urlParams = (array)$pms;
                            $view              = $clz->{$action}(...$args);
                            if ($view !== null) {
                                if (is_array($view)) {
                                    $view = new JsonView($view);
                                } else if (!$view instanceof View) {
                                    $view = new SimpleView($view);
                                } else if (!$aryArgs) {
                                    self::prepareView($view, $module, $clz, $action);
                                }
                            }

                            return $clz->afterRun($action, $view, $method);
                        }
                    }
                } catch (\Exception $e) {
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
     * @param string $subnamespace
     *
     * @return array array ($controllerClz,$action,$params )
     */
    public static function findApp($module, $action, $params, $namespace, $subnamespace = '') {
        if (is_numeric($action)) {
            array_unshift($params, $action);
            $action = 'index';
        }
        if ($subnamespace) {
            $module    .= DS . $subnamespace;
            $namespace .= '\\' . $subnamespace;
            $mclz      = null;
        } else {
            $mclz = App::getModuleByDir($module);
        }
        if ($action != 'index') {
            // Action Controller 的 index方法
            $controllerClz   = str_replace('-', '', ucwords($action, '-')) . 'Controller';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, 'index', $action];

            //子模块
            if (!$subnamespace) {
                $controller_file = MODULES_PATH . $module . DS . 'Router.php';
                if (is_file($controller_file)) {
                    $files [] = [$controller_file, $namespace . '\Router', $action, 'index'];
                }
            }
            // 默认controller的action方法
            $controllerClz   = 'IndexController';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, $action, 'index'];

            foreach ($files as $file) {
                list ($controller_file, $controllerClz, $action, $controller) = $file;
                if (is_file($controller_file)) {
                    include_once $controller_file;
                    if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
                        if ($action == 'index' && count($params) > 0) {
                            $action = array_shift($params);
                        } else if (is_subclass_of($controllerClz, SubModuleRouter::class)) {
                            //子模块
                            array_unshift($params, $action);
                            $action = 'index';
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
                include_once $controller_file;
                if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
                    return [
                        $controllerClz,
                        Router::removeSlash($action),
                        $params,
                        $controller_file,
                        'index',
                        $action
                    ];
                }
            }
        }
        //查找子模块
        if (!$subnamespace) {
            //子模块路由
            $controller_file = MODULES_PATH . $module . DS . 'Router.php';
            if (is_file($controller_file)) {
                $controllerClz = $namespace . '\\Router';
                include_once $controller_file;
                if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\SubModuleRouter')) {
                    array_unshift($params, $action);

                    return [
                        $controllerClz,
                        'index',
                        $params,
                        $controller_file,
                        'index',
                        'index'
                    ];
                }
            } else if ($mclz && $mclz->hasSubModule()) {
                array_unshift($params, $action);

                return [
                    'wulaphp\mvc\controller\SubModuleRouter',
                    'index',
                    $params,
                    null,
                    'index',
                    'index'
                ];
            }
        }

        return null;
    }

    /**
     * 准备视图.
     *
     * @param mixed                              $view
     * @param string                             $module
     * @param \wulaphp\mvc\controller\Controller $clz
     * @param string                             $action
     */
    public static function prepareView($view, $module, $clz, $action) {
        if ($view instanceof IModuleView) {
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