<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace m1\controllers;

use m2\classes\M2Prefix;
use wulaphp\mvc\controller\Controller;

class Math extends Controller {
    use M2Prefix;

    public function add($i = 0, $j = 0) {
        return 'result = ' . ($i + $j);
    }
}