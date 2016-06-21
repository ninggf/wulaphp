<?php
namespace home\classes;

use wulaphp\router\hook\IRouterHooks;
use wulaphp\plugin\Hook;
use home\CmsURLDispatcher;

class DispatcherHookImpl extends Hook implements IRouterHooks {

    /**
     * (non-PHPdoc)
     *
     * @see \wulaphp\router\hook\IRouterHooks::register()
     */
    public function register($router) {
        $router->register ( new CmsURLDispatcher (), 1 );
    }
}
