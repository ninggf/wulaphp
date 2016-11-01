<?php
namespace wulaphp\cache;

use wulaphp\conf\ConfigurationLoader;

class Cache implements \ArrayAccess {

	public $expire = 0;

	/**
	 * 取系统缓存管理器.
	 *
	 * @param string $type 缓存类型
	 *
	 * @return Cache
	 */
	public static function getCache($type = '') {
		static $caches = [];

		if (!$type) {
			$loader = new ConfigurationLoader();
			$cfg    = $loader->loadConfig('cache');
			$type   = $cfg->getb('type', CACHE_TYPE_REDIS);
		}
		if (!isset($caches[ $type ])) {
			if (APP_MODE == 'dev') {
				$cache = new Cache ();
			} else {
				if (!isset($cfg)) {
					$loader = new ConfigurationLoader();
					$cfg    = $loader->loadConfig('cache');
				}
				if (!$cfg->getb('enabled')) {
					return new Cache();
				}
				$cache = apply_filter('get_' . $type . '_cache', null, $cfg);
				if (!$cache instanceof Cache) {
					$cache = new Cache ();
				}
			}
			$caches[ $type ] = $cache;
		}

		return $caches[ $type ];
	}

	public function offsetExists($offset) {
		return $this->has_key($offset);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->add($offset, $value, $this->expire);
	}

	public function offsetUnset($offset) {
		$this->delete($offset);
	}

	/**
	 * 缓存数据.
	 *
	 * @param string $key    缓存唯一键值
	 * @param mixed  $value  要缓存的数据
	 * @param int    $expire 缓存时间
	 */
	public function add($key, $value, $expire = 0) {
	}

	/**
	 * 从缓存中取数据.
	 *
	 * @param string $key 缓存唯一键值.
	 *
	 * @return mixed 缓存数据,如果未命中则返回null
	 */
	public function get($key) {
		return null;
	}

	/**
	 * 删除一个缓存.
	 *
	 * @param string $key 缓存唯一键值
	 */
	public function delete($key) {
	}

	/**
	 * 清空所有缓存.
	 *
	 * @param boolean $check 缓存组
	 */
	public function clear($check = true) {
	}

	/**
	 * key是否存在.
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has_key($key) {
		return false;
	}
}
