<?php
/**
 * for redis cache
 */
bind('get_redis_cache', function ($cache, $cfg) {
    if (!$cache) {
        $cache = \wulaphp\cache\RedisCache::getInstance($cfg);
    }

    return $cache;
}, 100, 2);
/**
 * for memcached cache
 */
bind('get_memcached_cache', function ($cache, $cfg) {
    if (!$cache) {
        $cache = \wulaphp\cache\MemcachedCache::getInstance($cfg);
    }

    return $cache;
}, 100, 2);

bind('artisan\getCommands', function ($cmds) {
    $cmds['admin'] = new \wulaphp\command\AdminCommand();

    if (function_exists('pcntl_fork') && function_exists('posix_getpid')) {
        if (function_exists('socket_create')) {
            $cmds['service'] = new \wulaphp\command\ServiceCommand();
        }
    }

    return $cmds;
});