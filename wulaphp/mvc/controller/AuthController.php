<?php
namespace wulaphp\mvc\controller;

abstract class AuthController extends SessionController {

    protected $user;

    public function _beforeRun($action) {
        if (isset ( $this->permissions )) {
        }
    }
}