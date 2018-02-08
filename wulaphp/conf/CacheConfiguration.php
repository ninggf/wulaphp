<?php

namespace wulaphp\conf;

define('CACHE_TYPE_REDIS', 'redis');
define('CACHE_TYPE_MEMCACHED', 'memcached');

class CacheConfiguration extends Configuration {
	public function __construct() {
		parent::__construct('cache');
	}

	/**
	 * 添加redis配置.
	 *
	 * @param string  $host
	 * @param integer $port
	 * @param integer $db
	 * @param integer $timeout
	 * @param string  $auth
	 */
	public function addRedisServer($host, $port = 6379, $db = 0, $timeout = 5, $auth = '') {
		$this->settings['redis'] = [$host, $port, $db, $timeout, $auth];
	}

	/**
	 * 添加memcached配置.
	 *
	 * @param string  $host
	 * @param integer $port
	 * @param integer $weight
	 */
	public function addMemcachedServer($host, $port = 11211, $weight = 100) {
		$this->settings['memcached'][] = [$host, $port, $weight];
	}

	/**
	 * @param bool $enabled
	 */
	public function enabled($enabled = true) {
		$this->settings['enabled'] = $enabled;
	}

	/**
	 * 自定义缓存器配置.
	 *
	 * @param string $type
	 * @param mixed  $config
	 */
	public function addConfig($type, $config) {
		$this->settings[ $type ] = $config;
	}

	public function setDefaultCache($cache) {
		$this->settings['default'] = $cache;
	}
}