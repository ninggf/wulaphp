<?php
namespace home;

use wulaphp\router\IURLDispatcher;
use wulaphp\mvc\view\JsonView;

class CmsURLDispatcher implements IURLDispatcher {

    public function dispatch($url, $router, $parsedInfo) {
        return new JsonView ( $_SERVER );
    }
}

?>