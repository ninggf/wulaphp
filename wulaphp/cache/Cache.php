<?php

namespace wulaphp\cache;

use wulaphp\conf\ConfigurationLoader;

class Cache implements \ArrayAccess {
    public  $expire    = 0;
    private $isEnalbed = true;

    public function getName() {
        return '未启用';
    }

    /**
     * 取系统缓存管理器.
     *
     * @param string $type 缓存类型
     *
     * @return Cache
     */
    public static function getCache(string $type = ''):Cache {
        static $caches = [];
        if (!$type) {
            $loader = new ConfigurationLoader();
            $cfg    = $loader->loadConfig('cache');
            $type   = $cfg->get('default', CACHE_TYPE_REDIS);
        }

        if (!isset($caches[ $type ])) {
            if (!isset($cfg)) {
                $loader = new ConfigurationLoader();
                $cfg    = $loader->loadConfig('cache');
            }
            if (!$cfg->getb('enabled')) {
                $cache            = new Cache();
                $cache->isEnalbed = false;
                $caches[ $type ]  = $cache;

                return $cache;
            }
            $cache = apply_filter('get_' . $type . '_cache', null, $cfg);
            if (!$cache instanceof Cache) {
                $cache            = new Cache ();
                $cache->isEnalbed = false;
            }
            $caches[ $type ] = $cache;
        }

        return $caches[ $type ];
    }

    /**
     * 是否启用
     * @return bool
     */
    public function enabled():bool {
        return $this->isEnalbed;
    }

    public function offsetExists($offset) {
        return $this->has_key($offset);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->add($offset, $value, $this->expire);
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }

    /**
     * 缓存数据.
     *
     * @param string $key    缓存唯一键值
     * @param mixed  $value  要缓存的数据
     * @param int    $expire 缓存时间,单位秒.
     *
     * @return bool
     */
    public function add($key, $value, $expire = 0) {
        return true;
    }

    /**
     * 从缓存中取数据.
     *
     * @param string $key 缓存唯一键值.
     *
     * @return mixed 缓存数据,如果未命中则返回null
     */
    public function get($key) {
        return null;
    }

    /**
     * 删除一个缓存.
     *
     * @param string $key 缓存唯一键值
     *
     * @return bool
     */
    public function delete($key) {
        return true;
    }

    /**
     * 清空所有缓存.
     *
     * @param boolean $check 缓存组
     *
     * @return bool
     */
    public function clear($check = true) {
        return true;
    }

    /**
     * key是否存在.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function has_key($key) {
        return false;
    }
}
