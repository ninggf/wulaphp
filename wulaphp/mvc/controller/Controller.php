<?php
namespace wulaphp\mvc\controller;

use wulaphp\mvc\view\SmartyView;

abstract class Controller {

	protected $module;

	public function __construct($module) {
		$this->module = $module;
	}

	public function __invoke($tpl, $data = array(), $headers = array()) {
		if ($tpl{0} == '@') {
			$tpl = substr($tpl, 1) . '.tpl';
		} else {
			$tpl = $this->module . '/views/' . $tpl . '.tpl';
		}
		$view = new SmartyView ($data, $tpl, $headers);
	}

	public function _beforeRun($action) {
	}

	public function _afterRun($action, $view) {
		return $view;
	}
}