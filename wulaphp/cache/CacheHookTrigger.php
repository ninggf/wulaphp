<?php
namespace wulaphp\cache;

use wulaphp\plugin\Trigger;

class CacheHookTrigger extends Trigger implements ICacheAlter {
    /*
     * (non-PHPdoc) @see \wulaphp\cache\ICacheAlter::get_cache_manager()
     */
    public function get_cache_manager($cacher) {
        return $this->delegateAlter ( 'get_cache_manager', array (
            $cacher
        ) );
    }
}

?>