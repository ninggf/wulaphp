<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\mvc\view\View;
use wulaphp\util\Annotation;

/**
 * Base Controller.
 *
 * @package wulaphp\mvc\controller
 * @property-read Module                   $module        模块
 * @property-read \ReflectionObject        $reflectionObj 反射
 * @property-read \wulaphp\util\Annotation $ann           注解
 * @property-read string                   $clzName       类名
 * @property-read string                   $ctrName       小写类名
 * @property-read string                   $slag          类名格式化后的URL
 * @property-read string                   $action        动作
 */
abstract class Controller {
    private $_module;  // 所属模块
    private $_clzName; // 类名
    private $_ctrName; // 小写类名
    private $_slag;    // 类名格式化后的URL
    private $_action;  // 动作
    private $_reflectionObj;
    private $_ann;
    private $beforeFeatures = [];
    private $afterFeatures  = [];

    public function __construct(Module $module) {
        $this->_clzName       = get_class($this);
        $this->_module        = $module;
        $this->_reflectionObj = new \ReflectionObject($this);
        $this->_ann           = new Annotation($this->reflectionObj);
        $name                 = preg_replace('#(Controller)$#', '', $this->reflectionObj->getShortName());
        $this->_slag          = preg_replace_callback('/([A-Z])/', function ($ms) {
            return '-' . strtolower($ms[1]);
        }, lcfirst($name));
        $this->_ctrName       = strtolower($name);
        $this->parseTraits();
    }

    /**
     * 在运行之前调用。
     *
     * @param string            $action
     * @param \ReflectionMethod $refMethod
     *
     * @return View
     */
    public function beforeRun($action, $refMethod) {
        $view          = null;
        $this->_action = $action;
        if ($this->beforeFeatures) {
            foreach ($this->beforeFeatures as $feature) {
                $view = $this->$feature($refMethod, $view);
            }
        }

        return $view;
    }

    /**
     * @param string            $action
     * @param View|mixed        $view
     * @param \ReflectionMethod $method
     *
     * @return View
     */
    public function afterRun($action, $view, $method) {
        if ($this->afterFeatures) {
            foreach ($this->afterFeatures as $feature) {
                $view = $this->$feature($action, $view, $method);
            }
        }

        return $view;
    }

    private function parseTraits() {
        $parents = class_parents($this);
        unset($parents['wulaphp\mvc\controller\Controller']);
        $traits = class_uses($this);
        if ($parents) {
            foreach ($parents as $p) {
                $tt = class_uses($p);
                if ($tt) {
                    $traits = array_merge($traits, $tt);
                }
            }
        }
        if ($traits) {
            $traits = array_unique($traits);
            foreach ($traits as $tts) {
                $tts   = explode('\\', $tts);
                $fname = $tts[ count($tts) - 1 ];
                $func  = 'onInit' . $fname;
                if (method_exists($this, $func)) {
                    $this->$func();
                }
                $bfname = 'beforeRunIn' . $fname;
                if (method_exists($this, $bfname)) {
                    $this->beforeFeatures[] = $bfname;
                }
                $afname = 'afterRunIn' . $fname;
                if (method_exists($this, $afname)) {
                    $this->afterFeatures[] = $afname;
                }
            }
        }
        unset($parents, $traits);
    }

    public function __get($name) {
        $pname = '_' . $name;

        return isset($this->{$pname}) ? $this->{$pname} : null;
    }
}