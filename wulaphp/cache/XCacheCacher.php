<?php

namespace wulaphp\cache;
/**
 * Class XCacheCacher
 * @package wulaphp\cache
 * @internal
 */
class XCacheCacher extends Cache {
	public function getName() {
		return 'XCache';
	}

	public function add($key, $data, $expire = 0) {
		@xcache_set($key, $data);

		return true;
	}

	public function delete($key) {
		@xcache_unset($key);

		return true;
	}

	public function get($key) {
		return @xcache_get($key);
	}

	public function clear($check = true) {
		return @xcache_clear_cache(0);
	}

	public function has_key($key) {
		return @xcache_isset($key);
	}
}