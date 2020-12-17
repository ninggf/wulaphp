<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace subm\user\controllers;

use wulaphp\mvc\controller\Controller;

class Add extends Controller {
    public function addOp() {
        return pview('~testm/views/test/add', ['i' => 2, 'j' => 8]);
    }
}