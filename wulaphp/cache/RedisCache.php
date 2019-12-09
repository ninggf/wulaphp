<?php

namespace wulaphp\cache;

use wulaphp\conf\Configuration;
use wulaphp\util\RedisClient;

/**
 * Class RedisCache
 * @package wulaphp\cache
 * @internal
 */
class RedisCache extends Cache {
    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(\Redis $redis) {
        $this->redis = $redis;
    }

    public function getName() {
        return 'Redis';
    }

    /**
     * @param \wulaphp\conf\Configuration $cfg
     *
     * @return \wulaphp\cache\Cache
     * @throws
     */
    public static function getInstance(Configuration $cfg) {
        $cache = null;
        if (extension_loaded('redis')) {
            $redisConfig = $cfg->get('redis');
            if ($redisConfig) {
                [$host, $port, $db, $timeout, $auth] = $redisConfig;
                try {
                    $redis = RedisClient::getRedis([$host, $port, $timeout, $auth, $db, $cfg->getb('persistent')]);
                    $cache = new RedisCache($redis);
                } catch (\Exception $e) {
                }
            }
        }

        return $cache;
    }

    public function add($key, $value, $expire = 0) {
        $value = [$value];
        $value = @json_encode($value);
        if ($expire > 0) {
            return $this->redis->set($key, $value, $expire);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    public function get($key) {
        $value = $this->redis->get($key);
        if ($value) {
            $value = @json_decode($value, true);
            if ($value) {
                return $value[0];
            }
        }

        return null;
    }

    public function delete($key) {
        return $this->redis->del($key) > 0;
    }

    public function clear($check = true) {
        return $this->redis->flushDB();
    }

    public function has_key($key) {
        return $this->redis->exists($key) > 0;
    }

}