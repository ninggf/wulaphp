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

		/**
		 * @return \wulaphp\cache\Cache
		 */
		public static function init() {
			if (RtCache::$CACHE == null) {
				RtCache::$PRE = defined('APPID') && APPID ? APPID : WWWROOT;
				if (APP_MODE != 'pro') {
					RtCache::$CACHE = new Cache ();
				} else if (defined('RUN_IN_CLUSTER')) {//部署到集群中，使用REDIS
					$loader = new ConfigurationLoader();
					$cfg    = $loader->loadConfig('cluster');
					$cache  = $cfg->getb('enabled', false) ? RedisCache::getInstance($cfg) : null;
					if ($cache) {
						RtCache::$CACHE = $cache;
					} else {
						RtCache::$CACHE = new Cache();
					}
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

		public static function add($key, $data) {
			$key = md5(RtCache::$PRE . $key);
			RtCache::$CACHE->add($key, $data);

			return true;
		}

		public static function get($key) {
			$key = md5(RtCache::$PRE . $key);

			return RtCache::$CACHE->get($key);
		}

		public static function delete($key) {
			$key = md5(RtCache::$PRE . $key);

			return RtCache::$CACHE->delete($key);
		}

		public static function clear() {
			RtCache::$CACHE->clear();
		}

		public static function exists($key) {
			$key = md5(RtCache::$PRE . $key);

			return RtCache::$CACHE->has_key($key);
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
		if (APP_MODE == 'pro') {
			return $default;
		}
		if ($envs === null && is_file(CONFIG_PATH . '.env')) {
			$envs = @parse_ini_file(CONFIG_PATH . '.env');
			if (!$envs) {
				$envs = [];
			}
			if (isset($envs['debug'])) {
				$envs['debug'] = intval($envs['debug']);
			}
		}
		if (isset($envs[ $key ])) {
			$default = $envs[ $key ];
		}

		return $default;
	}
}