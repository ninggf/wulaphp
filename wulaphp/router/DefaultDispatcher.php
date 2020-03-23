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
 * @internal
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
    public function dispatch(string $url, Router $router, UrlParsedInfo $parsedInfo) {
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
                $module = DEFAULT_MODULE;
            } else if (($dir = App::checkUrlPrefix($module))) { # 使用模块URL前缀直接访问
                $prefix = $module;
                $module = $dir;
            }
        } else if ($len == 2) {
            $module = $controllers [0];
            if (App::checkUrlPrefix($module)) { # 前缀
                $prefix = $module;
                $module = $controllers[1];
            } else {
                $action = $controllers [1];
            }
        } else if ($len > 2) {
            $module = $controllers [0];
            if (App::checkUrlPrefix($module)) { # 前缀
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
            //检测是否设置别名
            $dir = App::id2dir($namespace);
            if ($dir && $dir != $module) {
                //模块需要通过别名访问.
                return null;
            }
            //是否绑定到指定的域名
            $domain = App::getModuleDomain($namespace);
            if ($domain && $domain != VISITING_HOST) {
                //模块不可用.
                return null;
            }
            $mm = App::getModuleById($namespace);
            if (!$mm->enabled) {
                //模块不可用.
                return null;
            }
            $ckey = 'rt#' . $url;
            $app  = RtCache::lget($ckey);
            if ($app && is_file($app[3])) {
                include_once $app[3];
                $nc = false;
            } else {
                $app = self::findApp($namespace, $action, $pms, $namespace);
                $nc  = true;
            }
            if ($app) {
                [$controllerClz, $action, $pms, , $controllerSlag, $actionSlag] = $app;
                if (in_array($action, ['beforerun', 'afterrun'])) {
                    return null;
                }
                if ($nc) {
                    RtCache::ladd($ckey, $app);
                }
                try {
                    $clz = new $controllerClz ($mm);

                    if ($clz instanceof Controller && $clz->slag == $controllerSlag) {
                        if (!$clz instanceof SubModuleRouter) {
                            $cprefix = '';
                            if (method_exists($clz->clzName, 'urlGroup')) {
                                $tmpPrefix = ObjectCaller::callClzMethod($clz->clzName, 'urlGroup');
                                $cprefix   = $tmpPrefix && isset($tmpPrefix[1]) ? $tmpPrefix[1] : '';
                            }
                            if (($cprefix || $prefix) && $cprefix != $prefix) {
                                RtCache::ldelete($ckey);

                                return null;
                            }
                        } else {
                            $clz->prefix = $prefix;
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
                                self::prepareView($rtn, $namespace, $clz, $action);

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
                                        $idx ++;
                                    }
                                }
                            }
                            $router->urlParams = (array)$pms;
                            $view              = $clz->{$action}(...$args);
                            if ($view !== null) {
                                if (is_array($view)) {
                                    $view = new JsonView($view);
                                } else if (!$view instanceof View) {
                                    if (is_object($view)) {
                                        return $view;
                                    }
                                    $view = new SimpleView($view);
                                } else if (!$aryArgs) {
                                    self::prepareView($view, $namespace, $clz, $action);
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
    public static function findApp(string $module, string $action, array $params, string $namespace, string $subnamespace = ''): ?array {
        if (is_numeric($action)) {
            array_unshift($params, $action);
            $action = 'index';
        }
        $parent = null;
        if ($subnamespace) {
            $module    .= DS . $subnamespace;
            $namespace .= '\\' . $subnamespace;
        } else {
            $parent = App::getModuleById($namespace);
        }
        $isParent = $parent && $parent->hasSubModule();
        if ($action != 'index') {
            $modulePath = MODULES_PATH . $module . DS;
            // Action Controller 的 index方法
            $controllerClz   = str_replace('-', '', ucwords($action, '-')) . 'Controller';
            $controller_file = $modulePath . 'controllers' . DS . $controllerClz . '.php';
            $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, 'index', $action];

            $controllerClz   = str_replace('-', '', ucwords($action, '-'));
            $controller_file = $modulePath . 'controllers' . DS . $controllerClz . '.php';
            $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, 'index', $action];

            if (!$isParent || $subnamespace || ($isParent && !is_dir($modulePath . $action . DS . 'controllers'))) {
                // 默认controller的action方法
                $controllerClz   = 'IndexController';
                $controller_file = $modulePath . 'controllers' . DS . $controllerClz . '.php';
                $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, $action, 'index'];

                $controllerClz   = 'Index';
                $controller_file = $modulePath . 'controllers' . DS . $controllerClz . '.php';
                $files []        = [$controller_file, $namespace . '\controllers\\' . $controllerClz, $action, 'index'];
            }

            foreach ($files as $file) {
                [$controller_file, $controllerClz, $act, $controller] = $file;
                if (is_file($controller_file)) {
                    include_once $controller_file;
                    if (is_subclass_of($controllerClz, 'wulaphp\mvc\controller\Controller')) {
                        if ($act == 'index' && count($params) > 0) {
                            $act = array_shift($params);
                        }

                        return [
                            $controllerClz,
                            Router::removeSlash($act),
                            $params,
                            $controller_file,
                            $controller,
                            $act
                        ];
                    }
                }
            }
        } else {
            // 默认Controller的index方法
            $controllerClz   = 'IndexController';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            if (!is_file($controller_file)) {
                $controllerClz   = 'Index';
                $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            }
            $controllerClz = $namespace . '\controllers\\' . $controllerClz;
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
        if ($parent && $parent->hasSubModule()) {
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
    public static function prepareView($view, string $module, Controller $clz, string $action) {
        if ($view instanceof IModuleView) {
            $tpl = $view->getTemplate();
            if ($tpl) {
                if ($tpl[0] == '~') {
                    $tpl = substr($tpl, 1);
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