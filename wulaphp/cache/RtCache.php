<?php
namespace wulaphp\cache {

	/* 运行时缓存 */
	use wulaphp\conf\ConfigurationLoader;

	class RtCache {
		/**
		 * @var Cache
		 */
		private static $CACHE;

		private static $PRE;

		public static function init() {
			RtCache::$PRE = APPID . '@';

			if (RtCache::$CACHE == null) {
				if (APP_MODE == 'dev') {
					RtCache::$CACHE = new Cache ();

					return;
				}
				$loader  = new ConfigurationLoader();
				$cluster = $loader->loadConfig('cluster');
				if ($cluster->getb('enabled')) {
					$type = $cluster->get('type', CLUSTER_TYPE_REDIS);
					if ($type == CLUSTER_TYPE_REDIS) {
						$cache = RedisCache::getInstance($cluster);
					} else {
						$cache = MemcachedCache::getInstance($cluster);
					}
					if ($cache instanceof Cache) {
						RtCache::$CACHE = $cache;
					} else {
						RtCache::$CACHE = new Cache ();
					}
				} else if (function_exists('apc_store')) {
					RtCache::$CACHE = new ApcCacher ();
				} else if (function_exists('xcache_get')) {
					RtCache::$CACHE = new XCacheCacher ();
				} else {
					RtCache::$CACHE = new Cache ();
				}
			}
		}

		/**
		 * 本机缓存,apc或xcache.
		 * @return Cache
		 */
		public static function getLocalCache() {
			if (function_exists('apc_store')) {
				return new ApcCacher ();
			} else if (function_exists('xcache_get')) {
				return new XCacheCacher ();
			} else {
				return new Cache ();
			}
		}

		public static function add($key, $data) {
			$key   = RtCache::$PRE . $key;
			$cache = RtCache::$CACHE;

			return $cache->add($key, $data);
		}

		public static function get($key) {
			$key   = RtCache::$PRE . $key;
			$cache = RtCache::$CACHE;

			return $cache->get($key);
		}

		public static function delete($key) {
			$key   = RtCache::$PRE . $key;
			$cache = RtCache::$CACHE;

			return $cache->delete($key);
		}

		public static function clear() {
			RtCache::$CACHE->clear();
		}

		public static function exists($key) {
			$key   = RtCache::$PRE . $key;
			$cache = RtCache::$CACHE;

			return $cache->has_key($key);
		}

		public static function getInfo() {
			$clz = get_class(self::$CACHE);
			if ($clz != 'Cache') {
				return $clz;
			}

			return 'Unkown';
		}
	}

	RtCache::init();
}
namespace {

	use wulaphp\cache\RtCache;

	/**
	 * 从.env中取配置.
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return string
	 */
	function env($key, $default = '') {
		static $envs = null;
		if (APP_MODE != 'dev' && $envs === null) {
			$cache = RtCache::getLocalCache();
			$envs  = $cache->get(APPID . '@envs');
			if (!$envs) {
				$envs = null;
			}
		}
		if ($envs === null && is_file(APPROOT . '.env')) {
			$envs = @parse_ini_file(APPROOT . '.env');
			if (!$envs) {
				$envs = [];
			}
			if (isset($envs['debug'])) {
				$envs['debug'] = constant($envs['debug']);
			}
			if (APP_MODE != 'dev') {
				$cache = RtCache::getLocalCache();
				$cache->add(APPID . '@envs', $envs);
			}
		}
		if (isset($envs[ $key ])) {
			$default = $envs[ $key ];
		}

		return $default;
	}
}