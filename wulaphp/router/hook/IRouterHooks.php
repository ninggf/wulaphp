<?php
namespace wulaphp\router\hook;

use wulaphp\router\Router;

interface IRouterHooks {

    /**
     * 注册分发器.
     *
     * @param Router $router
     */
    function register($router);
}