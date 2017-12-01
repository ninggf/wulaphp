<?php
/**
 * 集群配置文件.
 * User: Leo Ning.
 * Date: 13/10/2016 11:04
 */

namespace wulaphp\conf;

class ClusterConfiguration extends Configuration {

	public function __construct() {
		parent::__construct('cluster');
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
	 * @param bool $enabled
	 */
	public function enabled($enabled = true) {
		$this->settings['enabled'] = $enabled;
	}
}