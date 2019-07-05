<?php

namespace wulaphp\cache {

    use wulaphp\conf\ConfigurationLoader;

    //加载运行时缓存类文件
    if (!defined('RUN_IN_CLUSTER')) {
        if (extension_loaded('yac')) {
            include WULA_ROOT . 'wulaphp/cache/YacCache.php';
        } else if (function_exists('apcu_store')) {
            include WULA_ROOT . 'wulaphp/cache/ApcCacher.php';
        } else if (extension_loaded('xcache')) {
            include WULA_ROOT . 'wulaphp/cache/XCacheCacher.php';
        }
    }

    /**
     * Class RtCache - runtime cache
     * @package wulaphp\cache
     */
    class RtCache {
        /**
         * @var Cache
         */
        private static $CACHE;
        private static $LOCAK_CACHE;
        public static  $PREFIX;

        /**
         * 初始化运行时缓存.
         *
         * @param bool $force 强制初始化
         *
         * @return \wulaphp\cache\Cache
         */
        public static function init($force = false) {
            if (RtCache::$CACHE == null || $force) {
                RtCache::$PREFIX = defined('APPID') && APPID ? APPID : WWWROOT;
                if (!$force && APP_MODE != 'pro') {
                    RtCache::$CACHE = new Cache ();
                } else if (defined('RUN_IN_CLUSTER')) {//部署到集群中，使用REDIS
                    $cfg = ConfigurationLoader::loadFromFile('cluster');
                    try {
                        $cache = $cfg->getb('enabled', false) ? RedisCache::getInstance($cfg) : null;
                    } catch (\Exception $e) {
                        $cache = new Cache();
                    }
                    if ($cache) {
                        RtCache::$CACHE = $cache;
                    } else {
                        RtCache::$CACHE = new Cache();
                    }
                    unset($cfg);
                } else if (extension_loaded('yac')) {
                    RtCache::$CACHE = new YacCache();
                } else if (function_exists('apcu_store')) {
                    RtCache::$CACHE = new ApcCacher ();
                } else if (function_exists('xcache_get')) {
                    RtCache::$CACHE = new XCacheCacher ();
                } else {
                    RtCache::$CACHE = new Cache ();
                }
            }

            return RtCache::$CACHE;
        }

        /**
         * 向运行时缓存写入数据.
         *
         * @param string $key
         * @param mixed  $data
         *
         * @return bool
         */
        public static function add($key, $data) {
            $key = md5(RtCache::$PREFIX . $key);
            RtCache::$CACHE->add($key, $data);

            return true;
        }

        /**
         * 从运行时缓存读取数据.
         *
         * @param string $key
         *
         * @return mixed
         */
        public static function get($key) {
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$CACHE->get($key);
        }

        /**
         * 删除缓存数据.
         *
         * @param string $key
         *
         * @return bool
         */
        public static function delete($key) {
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$CACHE->delete($key);
        }

        /**
         * 清空运行时缓存.
         */
        public static function clear() {
            RtCache::$CACHE->clear();
        }

        /**
         * 缓存是否存在.
         *
         * @param string $key
         *
         * @return bool
         */
        public static function exists($key) {
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$CACHE->has_key($key);
        }

        /**
         * 获取运行时缓存实例.
         *
         * @return string
         */
        public static function getInfo() {
            $clz = get_class(self::$CACHE);
            if ($clz != 'Cache') {
                return $clz;
            }

            return 'Unkown';
        }

        /**
         * 获取本地运行时缓存.
         *
         * @return \wulaphp\cache\Cache
         */
        public static function local() {
            if (self::$LOCAK_CACHE) {
                return self::$LOCAK_CACHE;
            }
            if (extension_loaded('yac')) {
                $rtc = new YacCache();
            } else if (function_exists('apcu_store')) {
                $rtc = new ApcCacher();
            } else if (function_exists('xcache_get')) {
                $rtc = new XCacheCacher();
            } else {
                $rtc = new Cache();
            }
            self::$LOCAK_CACHE = $rtc;

            return self::$LOCAK_CACHE;
        }
    }
}

namespace {

    use wulaphp\cache\RtCache;

    /**
     * 从.env中取配置.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = '') {
        static $envs = null, $rtc = false;

        if ($rtc === false) {
            $rtc = RtCache::local();
        }

        if ($envs === null) {
            $evnf = CONFIG_PATH . '.env';
            $ckey = md5($evnf);
            $envs = $rtc->get($ckey);
            if (is_file($evnf) && ($mtime = intval(@filemtime($evnf)))) {
                if (!$envs || @$envs['dot_env_mtime'] < $mtime) {
                    $envs                  = @parse_ini_file($evnf);
                    $envs['dot_env_mtime'] = $mtime;
                }
            } else {
                $envs = null;
            }
            if (!$envs) {
                $envs = [];
            }
            if (isset($envs['debug'])) {
                $envs['debug'] = intval($envs['debug']);
            } else {
                $envs['debug'] = 100;
            }
            $rtc->add($ckey, $envs);
        }

        if (isset($envs[ $key ])) {
            $default = $envs[ $key ];
        }

        return $default;
    }
}