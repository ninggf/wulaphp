<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\mvc\view\View;

/**
 * Class Controller
 * @package wulaphp\mvc\controller
 */
abstract class Controller {
	/**
	 * @var \wulaphp\app\Module
	 */
	public $module;
	public $clzName;
	public $ctrName;
	public $slag;
	public $action;
	/**
	 * @var \ReflectionObject
	 */
	public  $reflectionObj;
	private $beforeFeatures = [];
	private $afterFeatures  = [];

	public function __construct(Module $module) {
		$this->clzName       = get_class($this);
		$this->module        = $module;
		$this->reflectionObj = new \ReflectionObject($this);
		$name                = preg_replace('#(Controller)$#', '', $this->reflectionObj->getShortName());
		$this->slag          = preg_replace_callback('/([A-Z])/', function ($ms) {
			return '-' . strtolower($ms[1]);
		}, lcfirst($name));
		$this->ctrName       = strtolower($name);
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
		$view         = null;
		$this->action = $action;
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
			foreach ($traits as $tt) {
				$tts   = explode('\\', $tt);
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
}