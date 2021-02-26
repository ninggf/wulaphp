<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace m2\user\controllers;

use m1\classes\M1Prefix;
use wulaphp\mvc\controller\Controller;

class IndexController extends Controller {
    use M1Prefix;

    public function index() {
        return 'admin/m2/user is ok';
    }
}