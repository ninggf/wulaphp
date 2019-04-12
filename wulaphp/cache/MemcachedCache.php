<?php

namespace wulaphp\cache;

use wulaphp\conf\Configuration;

class MemcachedCache extends Cache {
    /**
     * @var \Memcached
     */
    private $cache = null;

    /**
     *
     * @param \Memcached $cache
     */
    public function __construct(\Memcached $cache) {
        $this->cache = $cache;

    }

    public function getName() {
        return 'Memcached';
    }

    /**
     * @param Configuration $cfg
     *
     * @return null|\wulaphp\cache\MemcachedCache
     */
    public static function getInstance($cfg) {
        if (extension_loaded('memcached')) {
            try {
                $servers = $cfg->get('memcached');
                if ($servers) {
                    $memcache = new \Memcached ();
                    $memcache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 3);
                    $memcache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
                    $memcache->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, 2);
                    $memcache->setOption(\Memcached::OPT_REMOVE_FAILED_SERVERS, true);
                    $memcache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 1);
                    if (extension_loaded('igbinary') && defined('MEMCACHED_USE_IGBINARY')) {
                        $memcache->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_IGBINARY);
                    }
                    if ($memcache->addServers($servers)) {
                        return new MemcachedCache($memcache);
                    }
                }
            } catch (\Exception $e) {
                log_error($e->getMessage(), 'cache_memcached');
            }
        }

        return null;
    }

    public function add($key, $value, $expire = 0) {
        if ($expire > 0) {
            $expire = time() + $expire;
        }

        return $this->cache->set($key, $value, $expire);
    }

    public function clear($check = true) {
        return $this->cache->flush();
    }

    public function delete($key) {
        return $this->cache->delete($key);
    }

    public function get($key) {
        $value = $this->cache->get($key);
        if ($value === false && $this->cache->getResultCode() == \Memcached::RES_NOTFOUND) {
            return null;
        }

        return $value;
    }

    public function has_key($key) {
        $this->cache->get($key);

        return !($this->cache->getResultCode() == \Memcached::RES_NOTFOUND);
    }
}