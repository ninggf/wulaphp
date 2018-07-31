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
	/**
	 * Session ID
	 * @var string
	 */
	protected $sessionID;

	protected function onInitSessionSupport() {
		$expire          = App::icfg('expire', 0);
		$this->sessionID = (new Session ($expire))->start();
	}
}