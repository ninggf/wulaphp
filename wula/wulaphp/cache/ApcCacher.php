<?php
namespace wulaphp\cache;

/**
 * APC Cache.
 *
 * @author guangfeng.ning
 *
 */
class ApcCacher extends Cache {

    public function add($key, $data, $expire = 0) {
        apc_store ( $key, $data );
        return true;
    }

    public function delete($key) {
        apc_delete ( $key );
        return true;
    }

    public function get($key) {
        return apc_fetch ( $key );
    }

    public function clear($check = true) {
        return apc_clear_cache ( 'user' );
    }

    public function has_key($key) {
        return apc_exists ( $key );
    }
}