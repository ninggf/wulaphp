<?php
namespace home;

use wulaphp\plugin\Trigger;

defined ( 'APPROOT' ) or die ( 'cannot access this file directly!' );

Trigger::bind ( '\home\classes\DispatcherHookImpl' );

// 必须返回此值
return __NAMESPACE__;