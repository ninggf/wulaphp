<?php
namespace wulaphp\plugin;

<<<<<<< HEAD
use wulaphp\util\ObjectCaller;

=======
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
/**
 * 插件触发器基类.
 *
 * @author leo
 *
 */
abstract class Trigger {

    private static $pluginsCls = array ();

    private static $plugins = array ();

    private static $methodChain = array ();

    private $methodMap = array ();

    private $myimpls;

    public function __construct() {
        $this->myimpls = class_implements ( $this );
        $clsName = get_class ( $this );
        foreach ( $this->myimpls as $impl ) {
            // 实例化插件接口类.
            $ref = new \ReflectionClass ( $impl );
            $methods = $ref->getMethods ();
            if (! isset ( self::$plugins [$impl] )) {
                // 插件接口未实例化
                foreach ( self::$pluginsCls as $plg ) {
                    // 从注册的插件类中查找包括接口的插件.
                    if (is_subclass_of ( $plg, $impl )) {
                        // 接口匹配.
                        self::$plugins [$impl] = 1;
                        $plgClz = new $plg ();
                        if ($methods) {
                            self::prepareChain ( $plgClz, $impl, $methods );
                        }
                    }
                }
            }
            
            if ($methods) {
                foreach ( $methods as $m ) {
                    $name = $m->getName ();
                    $this->methodMap [$name] = $impl;
                }
            }
        }
    }

    /**
     * 绑定插件.
     *
     * @param string $pluginCls
     */
    final public static function bind($pluginCls) {
        self::$pluginsCls [] = $pluginCls;
    }

    /**
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function delegateAlter($method, array $args) {
        if (isset ( $this->methodMap [$method] )) {
            $impl = $this->methodMap [$method];
            if (isset ( self::$methodChain [$impl] [$method] )) {
                $methods = self::$methodChain [$impl] [$method];
                foreach ( $methods as $priority => $pmethods ) {
                    foreach ( $pmethods as $obj ) {
<<<<<<< HEAD
                        $arg = ObjectCaller::callObjMethod ( $obj [0], $obj [1], $args );
=======
                        $arg = $this->callObjMethod ( $obj [0], $obj [1], $args );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
                        $args [0] = $arg;
                    }
                }
            }
        }
        return $args [0];
    }

    /**
     *
     * @param string $method
     * @param array $args
     */
    protected function delegateFire($method, $args = array()) {
        if (isset ( $this->methodMap [$method] )) {
            $impl = $this->methodMap [$method];
            if (isset ( self::$methodChain [$impl] [$method] )) {
                $methods = self::$methodChain [$impl] [$method];
                foreach ( $methods as $priority => $pmethods ) {
                    foreach ( $pmethods as $obj ) {
<<<<<<< HEAD
                        $this->ObjectCaller ( $obj [0], $obj [1], $args );
=======
                        $this->callObjMethod ( $obj [0], $obj [1], $args );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
                    }
                }
            }
        }
    }

    /**
     * 取调用链条.
     *
     * @param Plugin $clss
     * @param string $impl
     * @return array
     */
    private static function prepareChain($clss, $impl, $methods) {
        if ($methods) {
            foreach ( $methods as $m ) {
                $name = $m->getName ();
                $priority = $clss->getPriority ( $name );
                self::$methodChain [$impl] [$name] [$priority] [] = array (
                    $clss,$name
                );
            }
            ksort ( self::$methodChain [$impl] [$name], SORT_NUMERIC );
        }
    }
<<<<<<< HEAD
=======

    /**
     * 调用方法
     *
     * @param Object $obj
     * @param string $method
     * @param array $args
     * @return mixed
     */
    private function callObjMethod($obj, $method, $args) {
        $cnt = count ( $args );
        switch ($cnt) {
            case 0 :
                return $obj->{$method} ();
            case 1 :
                return $obj->{$method} ( $args [0] );
            case 2 :
                return $obj->{$method} ( $args [0], $args [1] );
            case 3 :
                return $obj->{$method} ( $args [0], $args [1], $args [2] );
            case 4 :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3] );
            case 5 :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4] );
            case 6 :
            default :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5] );
        }
    }
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
}