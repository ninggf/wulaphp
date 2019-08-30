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

class AliasTest extends TestCase {
    public function testAlias() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/admin/m33/abc/');
        $this->assertEquals('abc is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m33/');
        $this->assertEquals('ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/m33/user');
        $this->assertEquals('admin/m3/user is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m33/user/profile/read');
        $this->assertEquals('uid is 888888', $content);
    }
}