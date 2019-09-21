<?php

namespace app\controllers;

use wulaphp\mvc\controller\Controller;

class IndexController extends Controller {

    public function index() {
        // 可以按需修改
        return template('index.tpl', ['nihao' => 'Hello wula']);
    }
}