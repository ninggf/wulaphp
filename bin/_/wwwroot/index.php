<?php
use wulaphp\app\App;

define('WWWROOT', __DIR__ . DIRECTORY_SEPARATOR);
include WWWROOT . '../bootstrap.php';
return App::run();
