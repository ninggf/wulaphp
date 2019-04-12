<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\View;
use wulaphp\router\DefaultDispatcher;
use wulaphp\router\Router;

/**
 * 仅供内部使用,千万不要继承它。
 *
 * @package wulaphp\mvc\controller
 * @property-read  array $routes 自定义本模块的路由处理器
 * @internal
 */
class SubModuleRouter extends Controller {
    public function __construct(Module $module) {
        parent::__construct($module);
        $this->slag = 'index';
        $routes     = isset($this->routes) && is_array($this->routes) ? $this->routes : $module->routes();
        if ($routes && is_array($routes)) {
            $ns = $module->getNamespace();
            foreach ($routes as $route => $cb) {
                if (is_callable($cb)) {
                    bind('router:' . $ns . '/' . $route, $cb, 1, 2);
                } else {
                    bind('router:' . $ns . '/' . $route, $ns . '\\' . $cb, 1, 2);
                }
            }
        }
    }

    /**
     * @param array ...$args
     *
     * @return null|\wulaphp\mvc\view\View
     * @throws \Exception
     */
    public final function index(...$args) {
        $len = count($args);
        switch ($len) {
            case 0:
                return null;
            case 1:
                $subname = array_shift($args);
                $action  = 'index';
                break;
            default:
                $subname = array_shift($args);
                $fParam  = $action = array_shift($args);
        }
        if (empty($subname) || empty($action)) {
            return null;
        }
        $module = $this->module->getDirname();
        $app    = DefaultDispatcher::findApp($module, $action, $args, $this->module->getNamespace(), $subname);
        if ($app) {
            list ($controllerClz, $action, $pms, , $controllerSlag, $actionSlag) = $app;
            if (in_array($action, ['beforerun', 'afterrun', 'geturlprefix'])) {
                return null;
            }
            try {
                $clz = new $controllerClz ($this->module);

                if ($clz instanceof Controller && $clz->slag == $controllerSlag) {
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
                        $ref        = $clz->reflectionObj;
                        $method     = $ref->getMethod($action);
                        $methodSlag = Router::addSlash($method->getName());
                        if (!$method->isPublic() || $methodSlag != $actionSlag) {
                            return null;
                        }
                        /* @var \ReflectionParameter[] $params */
                        $params      = $method->getParameters();
                        $paramsCount = count($params);
                        if ($paramsCount < count($pms)) {
                            return null;
                        }
                        $rtn = $clz->beforeRun($action, $method);

                        $module .= '/' . $subname;
                        //beforeRun可以返回view了
                        if ($rtn instanceof View) {
                            DefaultDispatcher::prepareView($rtn, $module, $clz, $action);

                            return $rtn;
                        }
                        $args = [];

                        if ($paramsCount) {
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
                        $view = $clz->{$action}(...$args);
                        if ($view !== null) {
                            if (is_array($view)) {
                                $view = new JsonView($view);
                            } else if (!$view instanceof View) {
                                $view = new SimpleView($view);
                            } else {
                                DefaultDispatcher::prepareView($view, $module, $clz, $action);
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

        //通过插件扩展路由
        $ns = 'router:' . $this->module->getNamespace() . '/' . $subname;
        if (isset($fParam)) {
            array_unshift($args, $fParam);
        }

        return apply_filter($ns, null, $args);
    }
}