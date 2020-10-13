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
        $date = date('Ymd H:i:s', $time);
        self::assertTrue(preg_match("/.+?:[0-5][05]:00\$/", $date) ? true : false, $date);

        $crontab = '* * ' . (int)date('H') . ' * * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i:s', $time);
        $d2   = date('Ymd H:00:00', strtotime('+1 day'));
        self::assertEquals($d2, $date);

        $crontab = '* * * ' . (int)date('d') . ' * *';
        $time    = \CrontabHelper::next_runtime($crontab);
        self::assertTrue($time >= time());
        $date = date('Ymd H:i:s', $time);
        $d1   = date('Ymd 00:00:00');
        $d2   = date('Ymd' . ' 00:00:00', strtotime('+1 month'));
        self::assertTrue($d1 == $date || $d2 == $date, $date);
    }

    public function testNextRuntime0() {
        $ctime   = strtotime('2020-10-01 00:00:00');
        $crontab = '*/5 * * * * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        self::assertEquals($time, $ctime);

        $crontab = '* */5 * * * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        self::assertTrue($time == $ctime);
    }

    public function testNextRuntime1() {
        $ctime  = strtotime('2020-10-01 00:00:01');
        $ctime2 = strtotime('2020-10-01 04:00:01');
        $ctime3 = strtotime('2020-10-04 00:00:01');

        $crontab = '*/5 * * * * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);

        self::assertEquals('2020-10-01 00:00:05', date('Y-m-d H:i:s', $time));

        $crontab = '* */5 * * * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        self::assertEquals('2020-10-01 00:05:00', date('Y-m-d H:i:s', $time));

        $crontab = '* * 3,5 * * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-01 03:00:00', $date);

        $time = \CrontabHelper::next_runtime($crontab, $ctime2);
        $date = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-01 05:00:00', $date);

        $crontab = '* * * 2-8 * *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-02 00:00:00', $date);

        $time = \CrontabHelper::next_runtime($crontab, $ctime3);
        $date = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-05 00:00:00', $date);

        $crontab = '* * * * 10 1';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-05 00:00:00', $date);

        $crontab = '* * * * 10 5';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-02 00:00:00', $date);

        $crontab = '* 1 * * 10 5';//10月每周五0点1分
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-02 00:01:00', $date);

        $crontab = '* * * 2 10 *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-02 00:00:00', $date);

        $crontab = '* * */2 1 10 *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-01 02:00:00', $date);

        $time = \CrontabHelper::next_runtime($crontab, $ctime2);
        $date = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-01 06:00:00', $date);

        $time = \CrontabHelper::next_runtime($crontab, $ctime2 - 1);
        $date = date('Y-m-d H:i:s', $time);
        self::assertEquals('2020-10-01 04:00:00', $date);

        $crontab = '* * * 2 1 *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2021-01-02 00:00:00', $date);

        $crontab = '* * 5 2 1 *';
        $time    = \CrontabHelper::next_runtime($crontab, $ctime);
        $date    = date('Y-m-d H:i:s', $time);
        self::assertEquals('2021-01-02 05:00:00', $date);
    }
}