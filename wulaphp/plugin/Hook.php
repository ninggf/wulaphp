<?php
namespace wulaphp\plugin;

/**
 * 插件.
 *
 * @author leo
 *
 */
abstract class Hook {

    /**
     * 方法优先级.
     *
     * @param string $method
     * @return number 方法的优先级，值越少优先级越高.
     */
    public function getPriority($method) {
        return 10;
    }
}