<?php

namespace m1;

use m1\classes\M1Prefix;
use wulaphp\app\App;
use wulaphp\app\Module;

/**
 * m1
 *
 * @package m1
 */
class M1Module extends Module {
    use M1Prefix;

    public function getName():string {
        return 'm1';
    }

    public function getDescription():string {
        return '描述';
    }

    public function getHomePageURL():string {
        return '';
    }
}

App::register(new M1Module());
// end of bootstrap.php