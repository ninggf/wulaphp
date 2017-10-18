<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\App;
use wulaphp\io\Session;

/**
 * 为控制器提供会话支持.
 *
 * @package wulaphp\mvc\controller
 */
trait SessionSupport {
	protected function onInitSessionSupport() {
		$expire = App::icfg('expire', 0);
		(new Session ($expire))->start();
	}
}