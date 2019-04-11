<?php
/*
 * 启用了SessionSupport, PassportSupport, RbacSupport特性的控制器.
 *
 * 通行证类型为admin，请根据需要提供相应的通行证实现.
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\auth\PassportSupport;
use wulaphp\auth\RbacSupport;
use wulaphp\mvc\view\SimpleView;

class AdminController extends Controller {
    use SessionSupport, PassportSupport, RbacSupport;
    protected $passportType = 'admin';
    protected $loginBack    = false;

    public function __construct(Module $module) {
        parent::__construct($module);
        $this->globalRbacSetting['login'] = true;
    }

    protected function needLogin($view) {
        $view = apply_filter('mvc\admin\needLogin', $view);
        if ($view === null) {
            $view = new SimpleView('need Login');
        }

        return $view;
    }

    protected function onLocked($view) {
        $view = apply_filter('mvc\admin\onLocked', $view);
        if ($view === null) {
            $view = new SimpleView('you were locked');
        }

        return $view;
    }

    protected function onDenied($message, $view) {
        $view = apply_filter('mvc\admin\onDenied', $view, $message);
        if ($view === null) {
            $view = new SimpleView('you are denied');
        }

        return $view;
    }
}