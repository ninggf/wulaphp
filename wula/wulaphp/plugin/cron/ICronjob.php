<?php
namespace wulaphp\plugin\cron;

interface ICronjob {

    function run($time);
}