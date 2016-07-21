<?php
namespace wulaphp\util;

class ObjectCaller {

    /**
     * 调用对像的方法，最多10个参数.
     *
     * @param Object $obj
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function callObjMethod($obj, $method, $args) {
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
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5] );
            case 7 :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5], $args [6] );
            case 8 :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5], $args [6], $args [7] );
            case 9 :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5], $args [6], $args [7], $args [8] );
            default :
                return $obj->{$method} ( $args [0], $args [1], $args [2], $args [3], $args [4], $args [5], $args [6], $args [7], $args [8], $args [9] );
        }
    }
}
