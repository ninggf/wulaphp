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

/**
 * 管理器控制器.
 *
 * @package wulaphp\mvc\controller
 */
class AdminController extends Controller {
    use SessionSupport, PassportSupport, RbacSupport;
    protected $passportType = 'admin';
    protected $loginBack    = false;

    /**
     * AdminController constructor.
     *
     * @param \wulaphp\app\Module $module
     */
    public function __construct(Module $module) {
        parent::__construct($module);
        $this->globalRbacSetting['login'] = true;
    }

    /**
     * 需要登录(触发`mvc\admin\needLogin`勾子)。
     *
     * @param \wulaphp\mvc\view\View $view
     *
     * @return mixed|\wulaphp\mvc\view\SimpleView
     */
    protected function needLogin($view) {
        $view = apply_filter('mvc\admin\needLogin', $view);
        if ($view === null) {
            $view = new SimpleView('need Login');
        }

        return $view;
    }

    /**
     * 用户锁定(触发`mvc\admin\onLocked`勾子)
     *
     * @param \wulaphp\mvc\view\View $view
     *
     * @return mixed|\wulaphp\mvc\view\SimpleView
     */
    protected function onLocked($view) {
        $view = apply_filter('mvc\admin\onLocked', $view);
        if ($view === null) {
            $view = new SimpleView('you were locked');
        }

        return $view;
    }

    /**
     * 用户无权限(触发`mvc\admin\onDenied`勾子)
     *
     * @param string                 $message 提示信息
     * @param \wulaphp\mvc\view\View $view
     *
     * @return mixed|\wulaphp\mvc\view\SimpleView
     */
    protected function onDenied($message, $view) {
        $view = apply_filter('mvc\admin\onDenied', $view, $message);
        if ($view === null) {
            $view = new SimpleView('you are denied');
        }

        return $view;
    }
}