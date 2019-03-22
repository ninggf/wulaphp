<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\controllers;

use wulaphp\mvc\controller\Controller;

class TestController extends Controller {
    public function add($i, $j = 1) {
        return ['i' => $i, 'j' => $j];
    }

    public function sub($x = 0, $y = 0) {
        return ['result' => $x - $y];
    }
}