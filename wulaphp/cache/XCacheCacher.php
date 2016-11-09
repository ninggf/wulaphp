<?php
namespace wulaphp\cache;

class XCacheCacher extends Cache {

	public function add($key, $data, $expire = 0) {
		@xcache_set($key, $data);

		return true;
	}

	public function delete($key) {
		@xcache_unset($key);

		return true;
	}

	public function get($key) {
		if (@xcache_isset($key)) {
			return @xcache_get($key);
		}

		return null;
	}

	public function clear($check = true) {
		return @xcache_clear_cache(0);
	}

	public function has_key($key) {
		return @xcache_isset($key);
	}
}