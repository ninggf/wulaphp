<?php
namespace wulaphp\router;

use wulaphp\mvc\view\View;

/**
 * 前置URL分发器接口.
 * Interface IURLPreDispatcher
 * @package wulaphp\router
 */
interface IURLPreDispatcher {

	/**
	 * 分发之前调用
	 *
	 * @param string $url    URL.
	 * @param Router $router 路由器.
	 * @param View   $view   前一个分发器返回的View实例.
	 *
	 * @return View View 实例.
	 */
	function preDispatch($url, $router, $view);
}