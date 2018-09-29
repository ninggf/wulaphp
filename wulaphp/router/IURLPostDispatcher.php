<?php
namespace wulaphp\router;

use wulaphp\mvc\view\View;

/**
 * 后置URL分发器接口.
 * Interface IURLPostDispatcher
 * @package wulaphp\router
 */
interface IURLPostDispatcher {

	/**
	 * 分发之后.
	 *
	 * @param string $url    URL.
	 * @param Router $router 路由器.
	 * @param View   $view   要渲染的view.
	 *
	 * @return View View 实例.
	 */
	function postDispatch($url, $router, $view);
}