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
        if (!is_dir(TMP_PATH)) {
            self::assertTrue(mkdir(TMP_PATH, 0755));
        }
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

    public function testKeepargs() {
        $url = keepargs('ni.html?a=1&b=2&c=3&d=4', ['b', 'c']);
        self::assertEquals('ni.html?b=2&c=3', $url);
    }

    public function testUnkeepargs() {
        $url = unkeepargs('ni.html?a=1&b=2&c=3&d=4', ['b', 'c']);
        self::assertEquals('ni.html?a=1&d=4', $url);
    }

    public function testSafeIds() {
        $ids = safe_ids('1,2,a,b,c,3');
        self::assertEquals('1,2,3', $ids);
        $ids2 = safe_ids('1-2-a-b-c-3', '-');
        self::assertEquals('1-2-3', $ids2);
        $ids = safe_ids('1,2,a,b,c,3', ',', true);
        self::assertContains('1', $ids);
        self::assertContains('2', $ids);
        self::assertContains('3', $ids);

        $ids = safe_ids2('1,2,a,b,c,3');
        self::assertContains('1', $ids);
        self::assertContains('2', $ids);
        self::assertContains('3', $ids);
    }

    public function testUrl_append_args() {
        self::assertEquals('ni.html?d=4&e=5', url_append_args('ni.html', ['d' => 4, 'e' => 5]));
        $url  = 'ni.html?a=1&b=2&c=3';
        $url1 = url_append_args($url, ['c' => 4, 'd' => 4, 'e' => 5]);
        self::assertEquals('ni.html?a=1&b=2&c=4&d=4&e=5', $url1);
        $url1 = url_append_args($url, ['c' => 4, 'd' => 4, 'e' => 5], false);
        self::assertEquals('ni.html?a=1&b=2&c=3&d=4&e=5', $url1);
    }

    public function testIn_atag() {
        $content = <<< CNT
        <div>adafad adfa adfasdf adsfas adfaf
        adsfasdf <p>adsfasdf adsfasdfad afdasdf adsfasdf adsfasdf adsfasdf
        adsfasdf adsfa<a href="#" title="这个厉害">China</a> adfa word adfasdf<a href="#" 
        class="adsfasd">nihao</a> adfasdf adfasdf adsfasdf</p>
        adsfasdf adsfasdf adsfasdf<img src="#" style="asdfasdf"> adsfasdf adfasdf asdfasdf adsfasdf</div>
CNT;

        $rst = in_atag($content, 'nihao');
        self::assertTrue($rst);
        $rst = in_atag($content, 'China');
        self::assertTrue($rst);
        $rst = in_atag($content, '厉害');
        self::assertTrue($rst);
        $rst = in_atag($content, 'word');
        self::assertTrue(!$rst);
        $rst = in_atag($content, '中国');
        self::assertTrue(!$rst);
        $rst = in_atag($content, 'style');
        self::assertTrue($rst);
    }

    public function testEnv() {
        $path = getenv('PATH');
        self::assertNotEmpty($path);
        $path1 = env('PATH');
        self::assertEquals($path, $path1);

        $path2 = env('sys.path');
        self::assertEquals($path, $path2);
    }

    public static function tearDownAfterClass() {
        @unlink(TMP_PATH);
    }
}
