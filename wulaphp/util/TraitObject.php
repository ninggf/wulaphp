<?php

namespace wulaphp\util;

abstract class TraitObject {
    protected $_t_traits = [];

    public function __construct(string ...$cls) {
        $parents = class_parents($this);
        $cls[]   = 'wulaphp\util\TraitObject';
        foreach ($cls as $c) {
            unset($parents[ $c ]);
        }

        $traits = class_uses($this);
        
        while ($parents) {
            $p  = array_pop($parents);
            $tt = class_uses($p);
            if ($tt) {
                $traits = array_merge($traits, $tt);
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