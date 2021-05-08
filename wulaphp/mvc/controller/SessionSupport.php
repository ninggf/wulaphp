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
    protected $sessionID = null;

    protected final function onInitSessionSupport() {
        $expire          = App::icfg('expire', 0);
        $this->_session  = new Session ($expire);
        $this->sessionID = $this->_session->start($this->sessionID);
    }

    /**
     * 销毁并更换session id。
     */
    protected function changeSessionId() {
        $this->_session->changeId();
    }

    /**
     * 销毁session。
     */
    protected final function destorySession() {
        $this->_session->destory();
    }

    /**
     * 关闭session。
     */
    protected final function closeSession() {
        $this->_session->close();
    }
}