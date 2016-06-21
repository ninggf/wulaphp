<?php
namespace home;

use wulaphp\router\IURLDispatcher;
use wulaphp\mvc\view\JsonView;

class CmsURLDispatcher implements IURLDispatcher {

    public function dispatch($url, $router) {
        return new JsonView ( [
            'ok' => BASE_URL,'OK1' => CONTEXT_URL
        ] );
    }
}

?>