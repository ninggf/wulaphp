<?php
namespace wulaphp\router;

use wulaphp\mvc\view\View;

/**
 * URL 分发器.
 *
 * @author Leo.
 *
 */
interface IURLDispatcher {
<<<<<<< HEAD

=======
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    /**
     * 分发URL.
     * 一旦有一个分发器返回View实例，则立即返回，停止分发其它的.
     *
     * @param string $url URL.
     * @param Router $router 路由器.
     * @return View View 实例.
     */
<<<<<<< HEAD
    function dispatch($url, $router, $parsedInfo);
=======
    function dispatch($url, $router);
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
}