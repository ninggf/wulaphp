<?php
namespace home;

use wulaphp\router\IURLDispatcher;
use wulaphp\mvc\view\JsonView;

class CmsURLDispatcher implements IURLDispatcher {

<<<<<<< HEAD
    public function dispatch($url, $router, $parsedInfo) {
        return new JsonView ( $_SERVER );
=======
    public function dispatch($url, $router) {
        return new JsonView ( [
            'ok' => BASE_URL,'OK1' => CONTEXT_URL
        ] );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    }
}

?>