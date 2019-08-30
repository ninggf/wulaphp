<?php

namespace m2;

use m2\classes\M2Prefix;
use wulaphp\app\App;
use wulaphp\app\Module;

/**
 * m2
 *
 * @package m2
 *
 * @subEnabled
 */
class M2Module extends Module {
    use M2Prefix;

    public function getName() {
        return 'm2';
    }

    public function getDescription() {
        return '描述';
    }

    public function getHomePageURL() {
        return '';
    }
}

App::register(new M2Module());
// end of bootstrap.php