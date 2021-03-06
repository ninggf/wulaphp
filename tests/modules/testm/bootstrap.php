<?php
/*
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm;

use wulaphp\app\App;
use wulaphp\app\Module;

class TestM extends Module {
    public function getName():string {
        return '模块一';
    }

    public function getDescription():string {
        return 'testm';
    }

    public function getHomePageURL():string {
        return 'http://www.wulaphp.com/';
    }
}

App::register(new TestM());