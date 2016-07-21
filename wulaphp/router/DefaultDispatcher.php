<?php
namespace wulaphp\router;

use wulaphp\app\App;
use wulaphp\plugin\Trigger;
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
     * @see \wulaphp\router\IURLDispatcher::dispatch()
     */
    public function dispatch($url, $router, $parsedInfo) {
        $controllers = explode ( '/', $url );
        $pms = array ();
        $len = count ( $controllers );
        if ($len == 0) {
            return null;
        } else if ($len == 1 && ! empty ( $controllers [0] )) {
            $module = $controllers [0];
            $action = 'index';
        } else if ($len == 2) {
            $module = $controllers [0];
            $action = $controllers [1];
        } else if ($len > 2) {
            $module = $controllers [0];
            $action = $controllers [1];
            $pms = array_slice ( $controllers, 2 );
        }
        $module = strtolower ( $module );
        $namespace = App::dir2id ( $module, true );
        if ($namespace) {
            $action = strtolower ( $action );
            $app = $this->findApp ( $module, $action, $pms, $namespace );
            if ($app) {
                list ( $controllerClz, $action, $pms ) = $app;
                try {
                    
                    $rm = strtolower ( $_SERVER ['REQUEST_METHOD'] );
                    $clz = new $controllerClz ( $module );
                    // 存在index_get,index_post,add_get add_post这新的方法.
                    if (method_exists ( $clz, $action . '_' . $rm )) {
                        $action = $action . '_' . $rm;
                    } else if (! method_exists ( $clz, $action )) {
                        array_unshift ( $pms, $action );
                        $action = 'index';
                    }
                    if (method_exists ( $clz, $action . '_' . $rm )) {
                        $action = $action . '_' . $rm;
                    }
                    
                    if (method_exists ( $clz, $action )) {
                        $ref = new \ReflectionObject ( $clz );
                        $method = $ref->getMethod ( $action );
                        $params = $method->getParameters ();
                        if (count ( $params ) < count ( $pms )) {
                            if (DEBUG == DEBUG_DEBUG) {
                                trigger_error ( 'the count of parameters of "' . $controllerClz . '::' . $action . '" does not match, except ' . count ( $params ) . ' but ' . count ( $pms ) . ' given.', E_USER_ERROR );
                            } else {
                                return null;
                            }
                        }
                        $view = $clz->_beforeRun ( $action );
                        if ($view) {
                            return $view;
                        }
                        $args = array ();
                        if ($params) {
                            $idx = 0;
                            foreach ( $params as $p ) {
                                $name = $p->getName ();
                                $def = isset ( $pms [$idx] ) ? $pms [$idx] : ($p->isDefaultValueAvailable () ? $p->getDefaultValue () : null);
                                $value = rqst ( $name, $def, true );
                                $args [] = $value;
                                $idx ++;
                            }
                        }
                        $view = ObjectCaller::callObjMethod ( $clz, $action, $args );
                        $view = $clz->_afterRun ( $action, $view );
                        return $view;
                    }
                } catch ( \ReflectionException $e ) {
                    if (DEBUG == DEBUG_DEBUG) {
                        trigger_error ( var_export ( $e, true ), E_USER_ERROR );
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
     * @param array $params
     */
    protected function findApp($module, $action, $params, $namespace) {
        if (is_numeric ( $action )) {
            array_unshift ( $params, $action );
            $action = 'index';
        }
        if ($action != 'index') {
            // Action Controller 的 index方法
            $controllerClz = ucfirst ( $action ) . 'Controller';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            $files [] = array (
                $controller_file,$namespace . '\controllers\\' . $controllerClz,'index'
            );
            // 默认controller的action方法
            $controllerClz = ucfirst ( $module ) . 'Controller';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            $files [] = array (
                $controller_file,$namespace . '\controllers\\' . $controllerClz,$action
            );
            
            foreach ( $files as $file ) {
                list ( $controller_file, $controllerClz, $action ) = $file;
                if (is_file ( $controller_file )) {
                    include $controller_file;
                    if (is_subclass_of ( $controllerClz, 'wulaphp\mvc\controller\Controller' )) {
                        if ($action == 'index' && count ( $params ) > 0) {
                            $action = array_shift ( $params );
                        }
                        return array (
                            $controllerClz,$action,$params
                        );
                    }
                }
            }
        } else {
            // 默认Controller的index方法
            $controllerClz = ucfirst ( $module ) . 'Controller';
            $controller_file = MODULES_PATH . $module . DS . 'controllers' . DS . $controllerClz . '.php';
            $controllerClz = $namespace . '\controllers\\' . $controllerClz;
            if (is_file ( $controller_file )) {
                include $controller_file;
                if (is_subclass_of ( $controllerClz, 'wulaphp\mvc\controller\Controller' )) {
                    return array (
                        $controllerClz,$action,$params
                    );
                }
            }
        }
        return false;
    }
}