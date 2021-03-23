<?php

namespace wulaphp\util;

abstract class TraitObject {
    protected $_t_traits = [];

    public function __construct() {
        $this->parseUsedTraits();
    }

    private function parseUsedTraits() {
        $parents = class_parents($this);
        unset($parents['wulaphp\util\TraitObject']);
        $traits = class_uses($this);
        if ($parents) {
            foreach ($parents as $p) {
                $tt = class_uses($p);
                if ($tt) {
                    $traits = array_merge($traits, $tt);
                }
            }
        }
        if ($traits) {
            $traits = array_unique($traits);
            foreach ($traits as $trait) {
                $tts  = explode('\\', $trait);
                $name = $tts[ count($tts) - 1 ];
                $func = 'onInit' . $name;
                if (method_exists($this, $func)) {
                    $this->$func();
                }
                $this->_t_traits[ $trait ] = $name;
            }
        }
        unset($parents, $traits);
    }
}