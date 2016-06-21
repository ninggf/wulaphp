<?php
namespace wulaphp\mvc\controller;

abstract class Controller {

    public function __construct() {
    }

    protected function beforeRun($method) {
    }

    protected function postRun($mehtod, $view) {
        return $view;
    }
}