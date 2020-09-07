<?php

namespace tests\Tests\common;

use PHPUnit\Framework\TestCase;

class CrontabHelperTest extends TestCase {
    public function testCrontab() {
        $crontab = '*/5 * * * * *';

        $checked = \CrontabHelper::check(1599429605, $crontab);

        self::assertTrue($checked);
        $checked = \CrontabHelper::check(1599429606, $crontab);

        self::assertTrue(!$checked);
    }

    public function testNextRuntime() {
        $crontab = '*/5 * * * * *';
        $time    = \CrontabHelper::next_runtime($crontab);

        self::assertTrue($time >= time());
        self::assertEquals(1, preg_match('/^[0-5][05]$/', date('s', $time)));

        $crontab = '* */5 * * * *';

        $time = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date  = date('Ymd H:i', $time);
        $date1 = date('Ymd H');
        self::assertTrue(preg_match("/^$date1:[0-5][05]\$/", $date) ? true : false, $date);

        $crontab = '* * ' . (int)date('H') . ' * * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i', $time);
        self::assertEquals(date('Ymd H:00', strtotime('+1 day')), $date);

        $crontab = '* * * ' . (int)date('d') . ' * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i', $time);
        self::assertEquals(date('Ymd' . ' 00:00', strtotime('+1 month')), $date);
    }
}