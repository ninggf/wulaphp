<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\module;

use PHPUnit\Framework\TestCase;
use wulaphp\util\CurlClient;

class UrlGroupTest extends TestCase {
    public function testM1WithAdminPrefix() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/admin/m1');
        $this->assertEquals('admin prefix is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/admin');
        $this->assertEquals('admin prefix is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/admin/m2/user');
        $this->assertEquals('admin/m2/user is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m2/user');
        $this->assertTrue(empty($content));
        $this->assertEquals(500, $curlient->errorCode);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m1/math/add/1?j=2');
        $this->assertEquals('result = 3', $content);
    }

    public function testM2WithVipPrefix() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/vip');
        $this->assertEquals('ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m2');
        $this->assertEquals('ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m2/abc');
        $this->assertEquals('abc is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/m2/abc');
        $this->assertTrue(empty($content));
        $this->assertEquals(500, $curlient->errorCode);

        $content = $curlient->get('http://127.0.0.1:9090/m2/user');
        $this->assertTrue(empty($content));
        $this->assertEquals(500, $curlient->errorCode);

        $curlient = CurlClient::getClient(5);
        $content  = $curlient->get('http://127.0.0.1:9090/vip/m2/abc/test/add/1/2');
        $this->assertEquals('{"result":3}', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m2/abc/test/add/1');
        $this->assertTrue(empty($content));
        $this->assertEquals(500, $curlient->errorCode);
    }
}