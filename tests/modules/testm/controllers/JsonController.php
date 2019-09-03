<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\controllers;

use wulaphp\mvc\controller\Controller;

class JsonController extends Controller {

    public function index() {
        $name = rqst('name');
        $age  = rqst('age');

        return ['name' => $name, 'age' => $age];
    }
}