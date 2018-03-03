<?php

namespace wulaphp\cache;

/**
 * APC Cache.
 *
 * @author guangfeng.ning
 *
 */
class ApcCacher extends Cache {

	public function getName() {
		return 'Apc';
	}

	public function add($key, $data, $expire = 0) {
		apcu_store($key, $data);

		return true;
	}

	public function delete($key) {
		apcu_delete($key);

		return true;
	}

	public function get($key) {
		$v = apcu_fetch($key, $rtn);

		return $rtn ? $v : null;
	}

	public function clear($check = true) {
		return apcu_clear_cache();
	}

	public function has_key($key) {
		return apcu_exists($key);
	}
}