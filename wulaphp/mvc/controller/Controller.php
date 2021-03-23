<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\io\Response;
use wulaphp\mvc\view\View;
use wulaphp\util\Annotation;
use wulaphp\util\TraitObject;

/**
 * Base Controller.
 *
 * @package wulaphp\mvc\controller
 * @property-read Module                   $module              模块
 * @property-read \ReflectionObject        $reflectionObj       反射
 * @property-read \wulaphp\util\Annotation $ann                 注解
 * @property-read \wulaphp\util\Annotation $methodAnn           正在执行动作的注解
 * @property-read string                   $clzName             类名
 * @property-read string                   $ctrName             小写类名
 * @property-read string                   $slag                类名格式化后的URL
 * @property-read string                   $action              动作
 */
abstract class Controller extends TraitObject {
    protected $_module;  // 所属模块
    protected $_clzName; // 类名
    protected $_ctrName; // 小写类名
    protected $_slag;    // 类名格式化后的URL
    protected $_action;  // 动作(方案)
    protected $_reflectionObj;
    protected $_ann;
    protected $_methodAnn;
    protected $_beforeFeatures = [];
    protected $_afterFeatures  = [];
    protected $_reqMethod;
    protected $_session;

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
        parent::__construct('wulaphp\mvc\controller\Controller');
        foreach ($this->_t_traits as $trait => $name) {
            $bName                   = 'beforeRunIn' . $name;
            $this->_beforeFeatures[] = $bName;
            $aName                   = 'afterRunIn' . $name;
            $this->_afterFeatures[]  = $aName;
        }
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
        if (!$this->_methodAnn instanceof Annotation) {
            $this->_methodAnn = new Annotation($refMethod);
        }
        if (defined('NEED_CHECK_REQ_M')) {
            $ms['post']   = $this->_methodAnn->has('post');
            $ms['put']    = $this->_methodAnn->has('put');
            $ms['patch']  = $this->_methodAnn->has('patch');
            $ms['delete'] = $this->_methodAnn->has('delete');
            $ms['get']    = $this->_methodAnn->has('get');
            $ms['*']      = !$ms['get'] && !$ms['post'] && !$ms['put'] && !$ms['patch'] && !$ms['delete'];
            if (!$ms['*']) {//指定了请求方法
                switch (NEED_CHECK_REQ_M) {
                    case 'post':
                    case 'put':
                    case 'delete':
                    case 'patch':
                        $blocked = !$ms[ NEED_CHECK_REQ_M ];
                        break;
                    case 'get':
                    default:
                        $blocked = $this->_methodAnn->has('post', 'put', 'delete', 'patch');
                }

                if ($blocked) {
                    Response::respond(405);
                }
            }
        }

        if ($this->_methodAnn->has('nobuffer')) {
            while (@ob_end_clean()) {
            };//close output buffers
            header("X-Accel-Buffering: no");
            header('Content-Type: text/octet-stream');
        }

        if ($this->_beforeFeatures) {
            foreach ($this->_beforeFeatures as $feature) {
                if (method_exists($this, $feature)) {
                    $view = $this->$feature($refMethod, $view);
                }
            }
        }

        return $view;
    }

    /**
     * 在业务方法执行完后调用.
     *
     * @param string            $action
     * @param View|mixed        $view
     * @param \ReflectionMethod $method
     *
     * @return View
     */
    public function afterRun($action, $view, $method) {
        if ($this->_afterFeatures) {
            foreach ($this->_afterFeatures as $feature) {
                if (method_exists($this, $feature)) {
                    $view = $this->$feature($action, $view, $method);
                }
            }
        }

        return $view;
    }

    public function __get($name) {
        $pname = '_' . $name;

        return isset($this->{$pname}) ? $this->{$pname} : null;
    }
}