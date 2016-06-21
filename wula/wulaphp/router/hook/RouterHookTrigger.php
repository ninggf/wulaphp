<?php
namespace wulaphp\router\hook;

use wulaphp\plugin\Trigger;

class RouterHookTrigger extends Trigger implements IRouterHooks {

    /**
     * (non-PHPdoc)
     *
     * @see \wulaphp\mvc\hook\IRouterHooks::register()
     */
    public function register($router) {
        $this->delegateFire ( 'register', array (
            $router
        ) );
    }
}