<?php

namespace wulaphp\mvc\controller;

use wulaphp\io\Session;

/**
 * 为控制器提供会话支持.
 *
 * @package wulaphp\mvc\controller
 */
trait SessionSupport {
	protected function onInitSessionSupport() {
		(new Session ())->start();
	}
}