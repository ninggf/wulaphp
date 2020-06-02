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

namespace login;

use wulaphp\app\App;
use wulaphp\app\Module;

class LoginModule extends Module {
    public function getName() {
        return '模块一';
    }

    public function getDescription() {
        return 'testm';
    }

    public function getHomePageURL() {
        return 'http://www.wulaphp.com/';
    }

    protected function bind(): ?array {
        return [
            'math\\add' => [
                function ($i, $j) {
                    return ($i + 1) * $j;
                },
                2,
                10
            ]
        ];
    }
}

App::register(new LoginModule());