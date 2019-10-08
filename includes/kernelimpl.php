<?php

use wulaphp\command\AdminCommand;
use wulaphp\command\CreateComamnd;
use wulaphp\command\ServeCommand;

bind('get_redis_cache', function ($cache, $cfg) {
    if (!$cache) {
        $cache = \wulaphp\cache\RedisCache::getInstance($cfg);
    }

    return $cache;
}, 100, 2);

bind('get_memcached_cache', function ($cache, $cfg) {
    if (!$cache) {
        $cache = \wulaphp\cache\MemcachedCache::getInstance($cfg);
    }

    return $cache;
}, 100, 2);

bind('artisan\getCommands', function ($cmds) {
    $cmds['admin']  = new AdminCommand();
    $cmds['create'] = new CreateComamnd();
    $cmds['serve']  = new ServeCommand();

    if (function_exists('pcntl_fork') && function_exists('posix_getpid')) {
        if (function_exists('socket_create')) {
            $cmds['service'] = new \wulaphp\command\ServiceCommand();
        }
    }

    return $cmds;
});