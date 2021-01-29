<?php

namespace app\controllers;

use wulaphp\mvc\controller\Controller;

class Index extends Controller {

    public function index() {
        // 按需修改
        return template('index.tpl', ['nihao' => 'Hello wula']);
    }
}