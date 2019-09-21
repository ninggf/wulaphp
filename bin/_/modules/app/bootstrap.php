<?php

namespace app;

use wulaphp\app\App;
use wulaphp\app\Module;

class AppModule extends Module {
    public function getName() {
        return '默认模块';
    }
}

App::register(new AppModule());