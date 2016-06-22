<?php
namespace home\controllers;

use wulaphp\mvc\controller\AuthController;

class UserController extends AuthController {

    public function index($op = '1') {
        return 'aaaa::' . $op . '::' . $this->module . '::' . sess_get ( 'name', 'no session' );
    }
}
?>