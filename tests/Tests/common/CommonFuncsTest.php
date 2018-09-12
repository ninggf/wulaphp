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
}