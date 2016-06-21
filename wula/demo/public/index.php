<?php
use wulaphp\app\App;
define ( 'WWWROOT', __DIR__ . '/' );
require WWWROOT . '../bootstrap.php';
App::route ();
//that is all.
