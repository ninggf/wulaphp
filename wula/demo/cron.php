<?php
/**
 * CRONTAB SCRIPT.
 */
require __DIR__ . '/bootstrap.php';

$crontab = new wulaphp\plugin\cron\Crontab ();

$crontab->run ( time () );

//that's all.