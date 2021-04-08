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
use wulaphp\cache\RtCache;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\View;
use wulaphp\router\DefaultDispatcher;
use wulaphp\router\Router;
use wulaphp\util\ObjectCaller;

/**
 * 仅供内部使用,千万不要继承它。
 *
 * @package wulaphp\mvc\controller
 * @property-read  array $routes 自定义本模块的路由处理器
 * @internal
 */
class SubModuleRouter extends Controller {
    public $prefix = null;

    public function __construct(Module $module) {
        parent::__construct($module);
        $this->slag = 'index';
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
                $subname = (string)array_shift($args);
                $action  = 'index';
                break;
            default:
                $subname = (string)array_shift($args);
                $action  = array_shift($args);
                if ($action == 'index') {
                    return null;
                }
        }

        if (empty($subname) || empty($action)) {
            return null;
        }

        $module = $this->module->getDirname();
        $ckey   = 'rt@' . Router::getRouter()->requestURI;
        $app    = RtCache::lget($ckey);# 从缓存读取
        if ($app && is_file($app[3])) {
            include_once $app[3];
            $nc = false;
        } else {
            $app = DefaultDispatcher::findApp($module, $action, $args, $this->module->getNamespace(), $subname);
            $nc  = true;
        }
        if ($app) {
            [$controllerClz, $action, $pms, , $controllerSlag, $actionSlag] = $app;
            if (in_array($action, ['beforerun', 'afterrun', '__get', '__set'])) {
                return null;
            }
            if ($nc) {
                RtCache::ladd($ckey, $app);
            }
            try {
                $clz = new $controllerClz ($this->module);

                if ($clz instanceof Controller && $clz->slag == $controllerSlag) {
                    $cprefix = '';
                    $prefix  = $this->prefix;
                    if (method_exists($clz->clzName, 'urlGroup')) {
                        $tmpPrefix = ObjectCaller::callClzMethod($clz->clzName, 'urlGroup');
                        $cprefix   = $tmpPrefix && isset($tmpPrefix[1]) ? $tmpPrefix[1] : '';
                    }
                    if (($cprefix || $prefix) && $cprefix != $prefix) {
                        RtCache::ldelete($ckey);

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
                    } else if (method_exists($clz, $action)) {
                        $actionFound = true;
                        defined('NEED_CHECK_REQ_M') or define('NEED_CHECK_REQ_M', $rqMethod);
                    } else { #后退查找index方法
                        $action     = 'index';
                        $actionSlag = 'index';
                        if (method_exists($clz, $action)) {
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

                        $params      = $method->getParameters();
                        $paramsCount = count($params);
                        if ($paramsCount < count($pms)) {
                            return null;
                        }

                        $args = []; # 获取URL参数
                        if ($paramsCount) {
                            $idx = 0;
                            foreach ($params as $p) {
                                $name  = $p->getName();
                                $def   = isset ($pms [ $idx ]) ? $pms [ $idx ] : ($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null);
                                $value = rqst($name, $def, true);
                                if ($value !== null) {
                                    $args [] = is_array($value) ? array_map(function ($v) {
                                        return is_array($v) ? $v : urldecode($v);
                                    }, $value) : urldecode($value);
                                    $idx ++;
                                }
                            }
                            if ($paramsCount != $idx) {
                                return null;
                            }
                        }

                        $rtn = $clz->beforeRun($action, $method);

                        $module .= '/' . $subname;
                        //beforeRun可以返回view了
                        if ($rtn instanceof View) {
                            DefaultDispatcher::prepareView($rtn, $module, $clz, $action);

                            return $rtn;
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

        return null;
    }
}