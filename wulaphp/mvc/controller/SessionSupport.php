<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\App;
use wulaphp\io\Session;

/**
 * 为控制器提供会话支持.
 *
 * @package wulaphp\mvc\controller
 * @property-read string|null $sessionID
 */
trait SessionSupport {
    protected final function onInitSessionSupport() {
        $expire          = App::icfg('expire', 0);
        $this->_session  = new Session ($expire);
        $this->sessionID = $this->_session->start(property_exists($this, 'sessionID') ? $this->sessionID : null);
    }

    /**
     * 销毁并更换session id。
     */
    protected function changeSessionId() {
        $this->_session->changeId();
    }

    /**
     * 销毁 session
     * @deprecated use destroySession
     */
    protected final function destorySession() {
        $this->_session->destory();
    }
    /**
     * 销毁 session
     */
    protected final function destroySession() {
        $this->_session->destory();
    }
    /**
     * 关闭 session
     */
    protected final function closeSession() {
        $this->_session->close();
    }
}