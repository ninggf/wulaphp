<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\common;

use PHPUnit\Framework\TestCase;
use wulaphp\app\App;

/**
 * Class AppTest
 * @package tests\Tests\common
 * @group   app
 */
class AppTest extends TestCase {

    public function testConfig() {
        $val = App::cfg('test1');
        self::assertEquals('test1 value', $val);

        $val = App::cfg('test2.key1');
        self::assertEquals('hello', $val);

        $val = App::acfg('test2.key2');
        self::assertEquals(['1', '2'], $val);

        $val = App::icfg('test3.key2', 10);
        self::assertEquals(10, $val);

        $val = App::icfgn('test4', 12);
        self::assertEquals(12, $val);

        $val = App::icfgn('test5', 123);
        self::assertEquals(123, $val);

        $val = App::icfgn('test6', 321);
        self::assertEquals(321, $val);

        $val = App::icfgn('test7', 100);
        self::assertEquals(100, $val);

        $val = App::icfgn('testx', 10000);
        self::assertEquals(10000, $val);

        # 自定义配置加载

        $val = App::cfg('test1@my');
        self::assertEquals('my value', $val);

        $val = App::cfg('test2.key1@my');
        self::assertEquals('hello wula', $val);

        $val = App::acfg('test2.key2@my');
        self::assertEquals(['2', '3'], $val);

        $val = App::cfg('test3@my');
        self::assertEquals('test3 dev value', $val);

        $val = App::cfg('test4@my');
        self::assertEquals('test4 value from env', $val);

        $val = App::cfg('test5@my');
        self::assertEquals('test5 value', $val);
    }
}