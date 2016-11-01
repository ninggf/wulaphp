<?php
/**
 *
 * User: Leo Ning.
 * Date: 13/10/2016 11:22
 */

namespace wulaphp\cache;

use wulaphp\conf\Configuration;

class RedisCache extends Cache {
	/**
	 * @var \Redis
	 */
	private $redis;

	public function __construct(\Redis $redis) {
		$this->redis = $redis;
	}

	/**
	 * @param \wulaphp\conf\Configuration $cfg
	 *
	 * @return \wulaphp\cache\Cache
	 */
	public static function getInstance(Configuration $cfg) {
		$cache = null;
		if (extension_loaded('redis')) {
			try {
				$redisConfig = $cfg->get('redis');
				if ($redisConfig) {
					$redis = new \Redis();
					list($host, $port, $db, $timeout, $auth) = $redisConfig;

					if ($host && $port && $redis->connect($host, $port, $timeout)) {
						$redis->select($db);
						if (extension_loaded('igbinary')) {
							$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
						} else {
							$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
						}
						if ($auth) {
							if ($redis->auth($auth)) {
								$cache = new RedisCache($redis);
							}
						} else {
							$cache = new RedisCache($redis);
						}
					}
				}
			} catch (\RedisException $e) {
				log_warn($e->getMessage(), 'cache_redis');
			}
		}

		return $cache;
	}

	public function add($key, $value, $expire = 0) {
		$value = array($value);
		$value = @json_encode($value);
		if ($expire > 0) {
			$this->redis->set($key, $value, $expire);
		} else {
			$this->redis->set($key, $value);
		}
	}

	public function get($key) {
		$value = $this->redis->get($key);
		if ($value) {
			$value = json_decode($value);
			if ($value) {
				return $value[0];
			}
		}

		return null;
	}

	public function delete($key) {
		$this->redis->del($key);
	}

	public function clear($check = true) {
		$this->redis->flushDB();
	}

	public function has_key($key) {
		return $this->redis->exists($key);
	}

}