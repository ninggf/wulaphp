<?php

namespace wulaphp\util;
/**
 * Class ObjectCaller
 * @package wulaphp\util
 * @internal
 */
class ObjectCaller {

    /**
     * 调用对像的方法，最多10个参数.
     *
     * @param object $obj
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function callObjMethod($obj, string $method, array $args = []) {
        return $obj->{$method} (...$args);
    }

    /**
     * @param string $clz
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function callClzMethod(string $clz, string $method, array $args = []) {
        return $clz::{$method}(...$args);
    }
}
