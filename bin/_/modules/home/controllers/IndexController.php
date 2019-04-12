<?php

namespace home\controllers;

use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;
use wulaphp\router\Router;

class IndexController extends Controller {

	public function index() {
		// 可以按需修改
		return template('index.tpl', ['nihao' => 'Hello wula']);
	}
}