<?php
namespace wulaphp\router;

interface IURLPostDispatcher {

    /**
     * 分发之后.
     *
     * @param string $url URL.
     * @param Router $router 路由器.
     * @param View $view 要渲染的view.
     * @return View View 实例.
     */
    function postDispatch($url, $router, $view);
}

?>