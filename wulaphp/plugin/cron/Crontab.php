<?php
namespace wulaphp\plugin\cron;

use wulaphp\plugin\Trigger;

class Crontab extends Trigger implements ICronjob {

    public function run($time) {
        $this->delegateFire ( 'run', array (
            $time
        ) );
    }
}
