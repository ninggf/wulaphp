<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace m3\user\controllers;

use m2\classes\M2Prefix;
use wulaphp\mvc\controller\Controller;

class Profile extends Controller {
    use M2Prefix;

    public function read() {
        return 'uid is 888888';
    }
}