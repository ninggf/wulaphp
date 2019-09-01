<?php
/*
 * This file is part of wulaphp.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app;

use wulaphp\app\App;
use wulaphp\app\Module;

class AppModule extends Module {
    public function getName() {
        return 'App Test Module';
    }
}

App::register(new AppModule());