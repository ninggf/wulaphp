<?php
namespace wulaphp\plugin;

abstract class Plugin {

    private static $binds = array ();

    private static $objs = array ();

    public function bind($point, $impl, $prority = 10) {
        self::$binds [$point] [$prority] [] = $impl;
    }

    protected function fire($point, $args = array()) {
        if (empty ( $point ) || ! isset ( self::$binds [$point] )) {
            return;
        }
    }

    protected function alter($point, $args) {
        if (empty ( $point ) || ! isset ( self::$binds [$point] )) {
            return $args [0];
        }
    }

    public abstract function getPoints();
    
}

?>