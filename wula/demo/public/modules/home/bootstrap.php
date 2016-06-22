<?php
<<<<<<< HEAD
=======
namespace home;

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
use wulaphp\plugin\Trigger;

defined ( 'APPROOT' ) or die ( 'cannot access this file directly!' );

<<<<<<< HEAD
// Trigger::bind ( '\home\classes\DispatcherHookImpl' );

// 必须返回此值
return 'home';
=======
Trigger::bind ( '\home\classes\DispatcherHookImpl' );

// 必须返回此值
return __NAMESPACE__;
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
