<?php
namespace wulaphp\hooks\session;

use wulaphp\plugin\Trigger;

class SessionHookTrigger extends Trigger implements ISessionHook {

    public function get_session_name($name) {
        return $this->delegateAlter ( 'get_session_name', array (
            $name
        ) );
    }
}
