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
     * 运行时缓存。
     *
     * @package wulaphp\cache
     */
    class RtCache {
        /**
         * @var Cache
         */
        private static $CACHE;
        /**
         * @var Cache
         */
        private static $LOCAL_CACHE;
        private static $lempty = true;
        public static  $empty  = false;
        /**
         * 缓存前缀.
         * @var string
         */
        public static $PREFIX;

        /**
         * 初始化运行时缓存.
         *
         * @param bool $force 强制初始化
         *
         * @return \wulaphp\cache\Cache
         */
        public static function init(bool $force = false) {
            if (RtCache::$CACHE == null || $force) {
                self::$empty = false;
                if (env('app.cluster')) {
                    defined('RUN_IN_CLUSTER') or define('RUN_IN_CLUSTER', true);
                }
                if (!$force && APP_MODE != 'pro') {
                    RtCache::$CACHE = new Cache ();
                    self::$empty    = true;
                } else if (defined('RUN_IN_CLUSTER')) {//部署到集群中，使用REDIS
                    $cfg = ConfigurationLoader::loadFromFile('cluster');
                    try {
                        $cache = $cfg->getb('enabled', false) ? RedisCache::getInstance($cfg) : null;
                    } catch (\Exception $e) {
                        $cache       = new Cache();
                        self::$empty = true;
                    }
                    if ($cache) {
                        RtCache::$CACHE = $cache;
                    } else {
                        RtCache::$CACHE = new Cache();
                        self::$empty    = true;
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
                    self::$empty    = true;
                }
            }

            return RtCache::$CACHE;
        }

        public static function initLocal() {
            if (!self::$LOCAL_CACHE) {
                if (($appid = getenv('APP_ID', true)) != '') {
                    RtCache::$PREFIX = $appid;
                } else {
                    RtCache::$PREFIX = WWWROOT;
                }
                if (extension_loaded('yac')) {
                    $rtc          = new YacCache();
                    self::$lempty = false;
                } else if (function_exists('apcu_store')) {
                    $rtc          = new ApcCacher();
                    self::$lempty = false;
                } else if (function_exists('xcache_get')) {
                    $rtc          = new XCacheCacher();
                    self::$lempty = false;
                } else {
                    $rtc = new Cache();
                }
                self::$LOCAL_CACHE = $rtc;
            }
        }

        /**
         * 向运行时缓存写入数据.
         *
         * @param string $key
         * @param mixed  $data
         *
         * @return bool
         */
        public static function add(string $key, $data) {
            if (self::$empty) {
                return true;
            }
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
        public static function get(string $key) {
            if (self::$empty) {
                return null;
            }
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
        public static function delete(string $key) {
            if (self::$empty) {
                return true;
            }
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$CACHE->delete($key);
        }

        /**
         * 清空运行时缓存.
         */
        public static function clear() {
            if (self::$empty) {
                return;
            }
            RtCache::$CACHE->clear();
        }

        /**
         * 缓存是否存在.
         *
         * @param string $key
         *
         * @return bool
         */
        public static function exists(string $key) {
            if (self::$empty) {
                return false;
            }
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
         * 向运行时缓存写入数据.
         *
         * @param string $key
         * @param mixed  $data
         *
         * @return bool
         */
        public static function ladd(string $key, $data) {
            if (self::$lempty) {
                return true;
            }
            $key = md5(RtCache::$PREFIX . $key);
            RtCache::$LOCAL_CACHE->add($key, $data);

            return true;
        }

        /**
         * 从运行时缓存读取数据.
         *
         * @param string $key
         *
         * @return mixed
         */
        public static function lget(string $key) {
            if (self::$lempty) {
                return null;
            }
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$LOCAL_CACHE->get($key);
        }

        /**
         * 删除缓存数据.
         *
         * @param string $key
         *
         * @return bool
         */
        public static function ldelete(string $key) {
            if (self::$lempty) {
                return true;
            }

            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$LOCAL_CACHE->delete($key);
        }

        /**
         * 清空运行时缓存.
         */
        public static function lclear() {
            if (self::$lempty) {
                return;
            }
            RtCache::$LOCAL_CACHE->clear();
        }

        /**
         * 缓存是否存在.
         *
         * @param string $key
         *
         * @return bool
         */
        public static function lexists(string $key) {
            if (self::$lempty) {
                return false;
            }
            $key = md5(RtCache::$PREFIX . $key);

            return RtCache::$LOCAL_CACHE->has_key($key);
        }
    }

    RtCache::initLocal();
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
    function env(string $key, $default = '') {
        static $envs = null, $senvs = null;
        if ($senvs === null) {
            $senvs = getenv();
        }

        $default = $senvs[ strtoupper(str_replace(['.', '-'], '_', $key)) ] ?? $default;

        if ($envs === null) {
            $evnf = CONFIG_PATH . '.env';
            $ckey = 'rt@.env';
            $envs = RtCache::lget($ckey);
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
            RtCache::ladd($ckey, $envs);
        }

        if (isset($envs[ $key ])) {
            $default = trim($envs[ $key ]);
            if (preg_match('/^\$\{([^\}]+)\}$/', $default, $ms)) {
                $default = getenv($ms[1], true);
            }
        }

        return $default;
    }
}