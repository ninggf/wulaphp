<?php

namespace home;

use wulaphp\app\App;
use wulaphp\app\Module;

class HomeModule extends Module {
    public function getName() {
        return '默认首页';
    }

    public function getDescription() {
        return '处理首页请求，可根据需要修改此模块以满足您的需求';
    }

    public function getHomePageURL() {
        return '';
    }

    public function getAuthor() {
        return 'wulaphp dev team';
    }
}

App::register(new HomeModule());