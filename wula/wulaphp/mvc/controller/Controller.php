<?php
namespace wulaphp\mvc\controller;

<<<<<<< HEAD
use wulaphp\mvc\view\SmartyView;

abstract class Controller {

    protected $module;

    public function __construct($module) {
        $this->module = $module;
    }

    public function __invoke($tpl, $data = array(), $headers = array()) {
        if ($tpl {0} == '@') {
            $tpl = substr ( $tpl, 1 ) . '.tpl';
        } else {
            $tpl = $this->module . '/views/' . $tpl . '.tpl';
        }
        $view = new SmartyView ( $data, $tpl, $headers );
    }

    public function _beforeRun($action) {
    }

    public function _afterRun($action, $view) {
=======
abstract class Controller {

    public function __construct() {
    }

    protected function beforeRun($method) {
    }

    protected function postRun($mehtod, $view) {
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
        return $view;
    }
}