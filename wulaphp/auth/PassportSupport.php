<?php

namespace wulaphp\auth;

use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\SmartyView;
use wulaphp\mvc\view\ThemeView;
use wulaphp\mvc\view\View;
use wulaphp\util\Annotation;

/**
 * 用户通行证认证特性。添加此特性后，在控制器中可直接通过$passport属性访问当前用户的通行证.
 *
 * 注：此特性依赖SessionSupport.
 *
 * @package wulaphp\auth
 * @property       string     $passportType
 * @property-read  Annotation $ann
 * @property-read  Annotation $methodAnn
 */
trait PassportSupport {
    /**
     * @var Passport
     */
    protected $passport;

    protected final function onInitPassportSupport() {
        if (!isset($this->passportType) && $this->ann instanceof Annotation && ($type = $this->ann->getString('passport'))) {
            $this->passportType = $type;
        }
        if (isset($this->passportType)) {
            $this->passport = Passport::get($this->passportType);
        } else {
            trigger_error('passportType property not found in Controller:' . get_class($this) . ', use default passport', E_USER_WARNING);
            $this->passport = Passport::get();
        }
    }

    /**
     * @param \Reflector $method
     * @param View       $view
     *
     * @return mixed
     */
    protected final function beforeRunInPassportSupport(\Reflector $method, $view) {
        //不需要登录
        $nologin = $this->methodAnn->has('nologin');
        if ($nologin) {
            return $view;
        }
        //用户登录
        if ($this->passport->uid) {
            if ($this->passport->status != 1) { //1为正常，其它值为锁定状态。
                return $this->onLocked($view);
            }
            $unlock = $this->methodAnn->has('unlock');
            if (!$unlock && $this->passport->screenLocked) { //不是解锁方法且用户已经锁屏。
                $rtn = $this->onScreenLocked($view);
                if ($rtn instanceof iew) {
                    return $rtn;
                } else if (is_array($rtn)) {
                    return new JsonView($rtn);
                } else if (is_string($rtn)) {
                    return new SimpleView($rtn);
                }
            }
        }

        return $view;
    }

    protected final function afterRunInPassportSupport($action, $view, $method) {
        if ($view instanceof SmartyView || $view instanceof ThemeView) {
            $view->assign('myPassport', $this->passport);
        }

        return $view;
    }

    /**
     * 用户被禁用时.
     *
     * @param mixed $view
     *
     * @return mixed
     */
    protected function onLocked($view) {
        return $view ? $view : 'user is locked';
    }

    /**
     * 用户锁定界面时.
     *
     * @param mixed $view
     *
     * @return mixed
     */
    protected function onScreenLocked($view) {
        return $view ? $view : 'screen is locked';
    }
}