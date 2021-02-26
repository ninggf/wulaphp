<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\controllers;

use wulaphp\mvc\controller\Controller;

class IndexController extends Controller {
    public function index() {
        return template('index.tpl', ['name' => 'Woola']);
    }

    public function wula() {
        return pview(['name' => 'this ia wula']);
    }
}