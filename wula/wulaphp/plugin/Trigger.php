<?php
namespace wulaphp\plugin;

use wulaphp\util\ObjectCaller;

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
                        $arg = ObjectCaller::callObjMethod ( $obj [0], $obj [1], $args );
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
                        $this->ObjectCaller ( $obj [0], $obj [1], $args );
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
}