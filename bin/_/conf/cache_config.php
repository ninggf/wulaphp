<?php
/*
 * 缓存配置
 */
$config = new \wulaphp\conf\CacheConfiguration();
$config->enabled(env('cache.enabled', 0));
$type = env('cache.type');
if ($type == 'redis') {
    $host    = env('cache.redis.host', 'localhost');
    $port    = env('cache.redis.port', 6379);
    $db      = env('cache.redis.db', 0);
    $timeout = env('cache.redis.timeout', 1);
    $auth    = env('cache.redis.auth');
    $config->addRedisServer($host, $port, $db, $timeout, $auth);
    $config->setDefaultCache(CACHE_TYPE_REDIS);
} else if ($type == 'memcached') {
    $config->setDefaultCache(CACHE_TYPE_MEMCACHED);
    $hosts = explode(',', env('cache.memcached.host', 'localhost'));
    $ports = explode(',', env('cache.memcached.port', 11211));
    $ws    = explode(',', env('cache.memcached.weight', 100));
    foreach ($hosts as $idx => $host) {
        $port = isset($ports[ $idx ]) ? $ports[ $idx ] : $ports[0];
        $w    = intval(isset($ws[ $idx ]) ? $ws[ $idx ] : $ws[0]);
        if ($w > 100 || $w < 1) {
            $w = 100;
        }
        $config->addMemcachedServer($host, $port, $w);
    }
}

return $config;