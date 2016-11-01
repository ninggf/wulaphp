<?php
namespace wulaphp\mvc\controller;

use wulaphp\app\Module;
use wulaphp\mvc\view\View;
use wulaphp\util\TraitObject;

/**
 * Class Controller
 * @package wulaphp\mvc\controller
 */
abstract class Controller extends TraitObject {
	/**
	 * @var \wulaphp\app\Module
	 */
	public $module;
	public $clzName;
	/**
	 * @var \ReflectionObject
	 */
	public  $reflectionObj;
	private $beforeFeatures = [];
	private $afterFeatures  = [];

	public function __construct(Module $module) {
		parent::__construct();
		$this->clzName       = get_class($this);
		$this->module        = $module;
		$this->reflectionObj = new \ReflectionObject($this);
		if ($this->myTraits) {
			foreach ($this->myTraits as $tt) {
				$fname = $tt;
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
	}

	/**
	 * 在运行之前调用。
	 *
	 * @param string            $action
	 * @param \ReflectionMethod $refMethod
	 */
	public function beforeRun($action, $refMethod) {
		if ($this->beforeFeatures) {
			foreach ($this->beforeFeatures as $feature) {
				$this->$feature($action, $refMethod);
			}
		}
	}

	/**
	 * @param string     $action
	 * @param View|mixed $view
	 *
	 * @return View
	 */
	public function afterRun($action, $view) {
		if ($this->afterFeatures) {
			foreach ($this->afterFeatures as $feature) {
				$view = $this->$feature($action, $view);
			}
		}

		return $view;
	}
}