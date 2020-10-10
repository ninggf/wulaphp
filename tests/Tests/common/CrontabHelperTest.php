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
        $date = date('Ymd H:i', $time);
        self::assertTrue(preg_match("/.+?:[0-5][05]\$/", $date) ? true : false, $date);

        $crontab = '* * ' . (int)date('H') . ' * * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i', $time);
        $d1   = date('Ymd H:i');
        $d2   = date('Ymd H:00', strtotime('+1 day'));
        self::assertTrue($d1 == $date || $d2 == $date, $date);

        $crontab = '* * * ' . (int)date('d') . ' * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i', $time);
        $d1   = date('Ymd 00:00');
        $d2   = date('Ymd' . ' 00:00', strtotime('+1 month'));
        self::assertTrue($d1 == $date || $d2 == $date, $date);
    }
}