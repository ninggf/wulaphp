<?php
/**
 * 集群配置文件.
 * User: Leo Ning.
 * Date: 13/10/2016 11:04
 */

namespace wulaphp\conf;

define('CLUSTER_TYPE_REDIS', 'redis');
define('CLUSTER_TYPE_MEMCACHED', 'memcached');

class ClusterConfiguration extends Configuration {

	public function __construct($type = CLUSTER_TYPE_REDIS) {
		parent::__construct('cluster');
		$this->settings['type'] = $type == CLUSTER_TYPE_REDIS ? CLUSTER_TYPE_REDIS : CLUSTER_TYPE_MEMCACHED;
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
	public function addRedisServer($host, $port = 6379, $db = 0, $timeout = 2, $auth = '') {
		$this->settings['redis'] = [$host, $port, $db, $timeout, $auth];
	}

	/**
	 * 添加memcached配置.
	 *
	 * @param string  $host
	 * @param integer $port
	 */
	public function addMemcachedServer($host, $port = 11211) {
		$this->settings['memcached'][] = [$host, $port];
	}

	/**
	 * @param bool $enabled
	 */
	public function enabled($enabled = true) {
		$this->settings['enabled'] = $enabled;
	}
}