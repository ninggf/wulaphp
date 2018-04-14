<?php
namespace {$namespace};

use wulaphp\app\App;
use wulaphp\app\Module;

/**
 * {$name}
 *
 * @package {$namespace}
 */
class {$module}Module extends Module {
    public function getName() {
        return '{$name}';
    }

    public function getDescription() {
        return '描述';
    }

    public function getHomePageURL() {
        return '';
    }
}

App::register(new {$module}Module());
// end of bootstrap.php