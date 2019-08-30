<?php

namespace m3;

use wulaphp\app\App;
use wulaphp\app\Module;

/**
 * m3
 *
 * @package m3
 *
 * @subEnabled
 */
class M3Module extends Module {

    public function getName() {
        return 'm3';
    }

    public function getDescription() {
        return '描述';
    }

    public function getHomePageURL() {
        return '';
    }
}

App::register(new M3Module());
// end of bootstrap.php