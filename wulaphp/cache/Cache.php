<?php
namespace wulaphp\cache;

use wulaphp\app\App;

class Cache implements \ArrayAccess {

    public $expire = 0;

    /**
     * 取系统缓存管理器.
     *
     * @return Cache
     */
    public static function getCache() {
        static $cache = false;
        if ($cache === false) {
            if (App::cfg ( 'develop_mode' )) {
                $cache = new Cache ();
            } else {
                $trigger = new CacheHookTrigger ();
                $cache = $trigger->get_cache_manager ( null );
                if (! $cache instanceof Cache) {
                    $cache = new Cache ();
                }
            }
        }
        return $cache;
    }

    public function offsetExists($offset) {
        return $this->has_key ( $offset );
    }

    public function offsetGet($offset) {
        return $this->get ( $offset );
    }

    public function offsetSet($offset, $value) {
        $this->add ( $offset, $value, $this->expire );
    }

    public function offsetUnset($offset) {
        $this->delete ( $offset );
    }

    /**
     * 缓存数据.
     *
     * @param string $key 缓存唯一键值
     * @param mixed $value 要缓存的数据
     * @param int $expire 缓存时间
     */
    public function add($key, $value, $expire = 0) {
    }

    /**
     * 从缓存中取数据.
     *
     * @param string $key 缓存唯一键值.
     * @return mixed 缓存数据,如果未命中则返回null
     */
    public function get($key) {
        return null;
    }

    /**
     * 删除一个缓存.
     *
     * @param string $key 缓存唯一键值
     */
    public function delete($key) {
    }

    /**
     * 清空所有缓存.
     *
     * @param boolean $check 缓存组
     */
    public function clear($check = true) {
    }

    /**
     * key是否存在.
     *
     * @param string $key
     * @return boolean
     */
    public function has_key($key) {
        return false;
    }
}
