<?php
/*
 * This file is part of wulaphp.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace teste;

use wulaphp\app\App;
use wulaphp\app\Extension;

class TestExtentsion extends Extension {
    public function getName() {
        return 'test extension';
    }

    protected function bind() {
        return ['math\\add' => ['&\teste\TestExtentsion', 2, 1]];
    }

    public static function mathAdd($i, $j) {
        return $i + $j;
    }
}

App::registerExtension(new TestExtentsion());