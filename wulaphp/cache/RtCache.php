<?php
namespace wulaphp\cache;

/* 运行时缓存 */
class RtCache {
    // 缓存与路径无关的数据
    private static $CACHE;

    private static $PRE;

    public static function init() {
        if (RtCache::$CACHE == null) {
            if (! file_exists ( TMP_PATH . 'cache' )) {
                @mkdir ( TMP_PATH . 'cache', 0755 );
            }
            RtCache::$PRE = APPID . '@';
            if (function_exists ( 'apc_store' )) {
                RtCache::$CACHE = new ApcCacher ();
            } else if (function_exists ( 'xcache_get' )) {
                RtCache::$CACHE = new XCacheCacher ();
            } else {
                RtCache::$CACHE = new Cache ();
            }
        }
    }

    public static function add($key, $data) {
        $key = RtCache::$PRE . $key;
        $cache = RtCache::$CACHE;
        return $cache->add ( $key, $data );
    }

    public static function get($key) {
        $key = RtCache::$PRE . $key;
        $cache = RtCache::$CACHE;
        return $cache->get ( $key );
    }

    public static function delete($key) {
        $key = RtCache::$PRE . $key;
        $cache = RtCache::$CACHE;
        return $cache->delete ( $key );
    }

    public static function clear($local = false) {
        RtCache::$CACHE->clear ();
    }

    public static function exists($key) {
        $key = RtCache::$PRE . $key;
        $cache = RtCache::$CACHE;
        return $cache->has_key ( $key );
    }

    public static function getInfo() {
        $clz = get_class ( self::$CACHE );
        if ($clz != 'Cache') {
            return $clz;
        }
        return 'Unkown';
    }
}
RtCache::init ();