<?php

namespace {$namespace}\controllers;

use wulaphp\mvc\controller\Controller;
/**
 * 默认控制器.
 */
class {$module}Controller extends Controller {
    /**
     * 默认控制方法.
     */
	public function index() {
        // TODO: 你的代码写在这里
	    $data = ['module' => '{$module}'];

        return view($data);
    }
}