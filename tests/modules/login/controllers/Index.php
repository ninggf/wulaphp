<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\controllers;

use wulaphp\auth\PassportSupport;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\controller\SessionSupport;
use wulaphp\mvc\view\SimpleView;

/**
 * Class Index
 * @package  tests\modules\login\controllers
 * @passport viptest
 */
class Index extends Controller {
    use SessionSupport, PassportSupport;
    protected $sessionID = 'aaaaabbbbccccdddd';

    /**
     * @unlock
     * @sessWrite
     * @return string
     */
    public function index() {
        try {
            $this->passport->login(1);

            return $this->passport->username . ' is logined';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @sessWrite
     * @return string
     */
    public function lock() {
        if ($this->passport->isLogin) {
            $this->passport->lockScreen();

            return 'locked';
        } else {
            return 'login please';
        }
    }

    public function view() {
        if ($this->passport->uid) {
            return 'haha you got it';
        } else {
            return 'login please';
        }
    }

    /**
     * @unlock
     *
     * @param $passwd
     * @sessWrite
     * @return string
     */
    public function unlock($passwd) {
        $this->passport->unlockScreen($passwd);

        return 'unlocked';
    }

    protected function onScreenLocked($view) {
        return new SimpleView('you are locked!');
    }
}