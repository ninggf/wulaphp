<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\App;
use wulaphp\io\Session;

/**
 * 为控制器提供会话支持.
 *
 * @package wulaphp\mvc\controller
 * @property string $sessionID session id
 */
trait SessionSupport {
    protected final function onInitSessionSupport() {
        $expire          = App::icfg('expire', 0);
        $this->sessionID = (new Session ($expire))->start($this->sessionID);
    }
}