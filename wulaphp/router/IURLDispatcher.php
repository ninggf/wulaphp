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

    /**
     * 分发URL.
     * 一旦有一个分发器返回View实例，则立即返回，停止分发其它的.
     *
     * @param string        $url        URL.
     * @param Router        $router     路由器.
     * @param UrlParsedInfo $parsedInfo URL解析信息.
     *
     * @return View View 实例.
     */
    function dispatch(string $url, Router $router, UrlParsedInfo $parsedInfo): ?View;
}