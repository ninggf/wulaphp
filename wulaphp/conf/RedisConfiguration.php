<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\conf;
/**
 * Redis配置.
 *
 * @package wulaphp\conf
 */
class RedisConfiguration extends Configuration {

	public function __construct() {
		parent::__construct('redis');
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
		$this->settings = [
			'host'    => $host,
			'port'    => $port,
			'db'      => $db,
			'timeout' => $timeout,
			'auth'    => $auth
		];
	}

	public function host($host) {
		$this->settings['host'] = $host;
	}

	public function port($port) {
		$this->settings['port'] = intval($port);
	}

	public function db($db) {
		$this->settings['db'] = intval($db);
	}

	public function timeout($timeout) {
		$this->settings['timeout'] = intval($timeout);
	}

	public function auth($auth) {
		$this->settings['auth'] = $auth;
	}
}