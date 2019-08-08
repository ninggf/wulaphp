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

namespace subm;

use wulaphp\app\App;
use wulaphp\app\Module;
use wulaphp\router\Router;

class SubM extends Module {
    public function getName() {
        return '模块2';
    }

    public function getDescription() {
        return '子模块测试';
    }

    public function getHomePageURL() {
        return 'http://www.wulaphp.com/';
    }

    public function hasSubModule() {
        return true;
    }
}

App::register(new SubM());