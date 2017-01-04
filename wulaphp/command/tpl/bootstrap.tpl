<?php
namespace {$namespace};

use wulaphp\app\App;
use wulaphp\app\Module;

class {$module}Module extends Module {
    public function getName() {
        return '模块的名字';
    }

    public function getDescription() {
        return '模块的描述';
    }

    public function getHomePageURL() {
        return '模块的URL';
    }
}

App::register(new {$module}Module());