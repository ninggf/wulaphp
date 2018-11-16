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

/**
 * Class CommonFuncsTest
 * @package tests\Tests\common
 * @group   common
 */
class CommonFuncsTest extends TestCase {
    public function testGet_then_unset() {
        $ary  = ['name' => 'wula', 'name1' => 'wula1'];
        $name = get_then_unset($ary, 'name');
        self::assertArrayNotHasKey('name', $ary);
        self::assertArrayHasKey('name1', $ary);
        self::assertArrayHasKey('name', $name);
        self::assertEquals('wula', $name['name']);
    }

    public function testUnique_filename() {
        $f1 = unique_filename(TMP_PATH, 'test.txt');
        self::assertEquals('test.txt', $f1);
        self::assertTrue(@file_put_contents(TMP_PATH . 'test.txt', 'test') > 0);
        $f2 = unique_filename(TMP_PATH, 'test.txt');
        self::assertEquals('test1.txt', $f2);
        @unlink(TMP_PATH . 'test.txt');
    }

    public function testPure_comman_string() {
        $string = pure_comman_string('a,,b,,，c,   d,中国');
        self::assertEquals('a,b,c,d,中国', $string);
    }
}