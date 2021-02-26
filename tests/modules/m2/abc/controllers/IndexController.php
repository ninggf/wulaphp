<?php

namespace m2\abc\controllers;

use m2\classes\M2Prefix;
use wulaphp\mvc\controller\Controller;

/**
 * 默认控制器.
 */
class IndexController extends Controller {
    use M2Prefix;

    /**
     * 默认控制方法.
     */
    public function index() {
        return 'abc is ok';
    }
}