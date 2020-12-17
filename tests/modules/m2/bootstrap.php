<?php

namespace m2;

use m2\classes\M2Prefix;
use wulaphp\app\App;
use wulaphp\app\Module;
use ZipStream\Stream;

/**
 * m2
 *
 * @package m2
 *
 * @subEnabled
 */
class M2Module extends Module {
    use M2Prefix;

    public function getName():string {
        return 'm2';
    }

    public function getDescription():string {
        return '描述';
    }

    public function getHomePageURL():string {
        return '';
    }
}

App::register(new M2Module());
// end of bootstrap.php