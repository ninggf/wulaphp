<?php
namespace wulaphp\conf;

/**
 * 数据库配置.
 *
 * @author leo
 *
 */
class DatabaseConfiguration extends Configuration {

	public function __construct($name) {
		parent::__construct($name);
		$this->settings = array('driver' => 'MySQL', 'port' => '3306', 'host' => 'localhost', 'dbname' => '', 'user' => 'root', 'password' => '888888', 'encoding' => 'UTF8');
	}

	public function host($host) {
		$this->settings ['host'] = $host;
	}

	public function port($port) {
		$this->settings ['port'] = $port;
	}

	public function driver($driver) {
		$this->settings ['driver'] = $driver;
	}

	public function dbname($dbname) {
		$this->settings ['dbname'] = $dbname;
	}

	public function user($user) {
		$this->settings ['user'] = $user;
	}

	public function password($password) {
		$this->settings ['password'] = $password;
	}

	public function encoding($encoding) {
		$this->settings ['encoding'] = $encoding;
	}

	public function options($options) {
		$this->settings ['options'] = $options;
	}

	public function __toString() {
		return implode('_', $this->settings);
	}
}
