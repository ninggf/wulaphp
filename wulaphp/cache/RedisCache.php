<?php

namespace wulaphp\cache;

use wulaphp\conf\Configuration;
use wulaphp\util\RedisClient;

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
			$redisConfig = $cfg->get('redis');
			if ($redisConfig) {
				list($host, $port, $db, $timeout, $auth) = $redisConfig;
				$redis = RedisClient::getRedis([$host, $port, $timeout, $auth, $db]);
				if ($redis) {
					$cache = new RedisCache($redis);
				}
			}
		}

		return $cache;
	}

	public function add($key, $value, $expire = 0) {
		$value = [$value];
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
			$value = @json_decode($value, true);
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