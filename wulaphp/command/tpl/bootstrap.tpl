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
}

App::register(new {$module}Module());
// end of bootstrap.php