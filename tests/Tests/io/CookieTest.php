<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\io;

use PHPUnit\Framework\TestCase;
use wulaphp\util\CurlClient;

class CookieTest extends TestCase {
    public function testCookie() {
        $curlient = CurlClient::getClient(5);

        $cookie             = new \stdClass();
        $cookie->value      = '123';
        $cookies['ocookie'] = $cookie;

        $content = $curlient->withCookies($cookies)->get('http://127.0.0.1:9090/testm/test/scookie');

        self::assertEquals('cookie set', $content);
        self::assertNotEmpty($cookies);
        self::assertArrayHasKey('test-sc', $cookies);
        self::assertEquals('123', $cookies['test-sc']->value);
        self::assertContains('HttpOnly', $cookies['test-sc']->option);

        $curlient->reset();
        $cookie->value      = '789';
        $cookies['ocookie'] = $cookie;

        $content = $curlient->withCookies($cookies)->get('http://127.0.0.1:9090/testm/test/scookie?ok=okk');

        self::assertEquals('cookie set:okk', $content);
        self::assertNotEmpty($cookies);
        self::assertArrayHasKey('test-sc', $cookies);
        self::assertEquals('789', $cookies['test-sc']->value);
        self::assertContains('HttpOnly', $cookies['test-sc']->option);
    }

    public function testMultiExecute() {
        $cookie             = new \stdClass();
        $cookie->value      = '123';
        $cookies['ocookie'] = $cookie;
        $g                  = CurlClient::getClient()->withCookies($cookies)->prepareGet('http://127.0.0.1:9090/testm/test/scookie');

        $cookie              = new \stdClass();
        $cookie->value       = 'abc=321;,测试';
        $cookies2['ocookie'] = $cookie;
        $p                   = CurlClient::getClient()->withCookies($cookies2);
        $p->useJsonBody()->preparePost('http://127.0.0.1:9090/testm/test/scookie', ['testT' => '测试']);

        $rts = CurlClient::execute([$g, $p]);

        self::assertTrue(count($rts[0]) == 2, var_export($rts, true));

        self::assertEquals('cookie set', $rts[0][0]);
        self::assertEquals('cookie post:测试', $rts[0][1]);

        self::assertArrayHasKey('test-sc', $cookies);
        self::assertEquals('123', $cookies['test-sc']->value);
        self::assertContains('HttpOnly', $cookies['test-sc']->option);

        self::assertArrayHasKey('test-sc', $cookies2);
        self::assertEquals('abc=321;,测试', $cookies2['test-sc']->value);
        self::assertContains('HttpOnly', $cookies2['test-sc']->option);
    }
}