<?php

namespace app;

use wulaphp\app\App;
use wulaphp\app\Module;
use wulaphp\io\Response;

class AppModule extends Module {
    public function getName(): string {
        return '默认模块';
    }

    protected function bind(): ?array {
        return [
            'router\parse_url' => function ($url) {
                if ($url == 'app') {
                    Response::redirect('/');
                }

                if ($url == 'index.html') {
                    $url = 'app';
                }

                return $url;
            }
        ];
    }
}

App::register(new AppModule());