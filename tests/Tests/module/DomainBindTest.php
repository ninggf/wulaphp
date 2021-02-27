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
use wulaphp\app\App;

class DomainBindTest extends TestCase {
    public function testDomainBind() {
        $rtn = $this->httpGet('/test/add', 'login.wulaphp.com');
        self::assertNotEmpty($rtn);
        self::assertEquals('{"i":"","j":"1"}', $rtn);

        //不能访问
        $rtn = $this->httpGet('/test/add', '127.0.0.1');
        self::assertNotEmpty($rtn);
        self::assertContains('no route for /test/add', $rtn);

        //不能访问
        $rtn = $this->httpGet('/testm/test/sub', 'login.wulaphp.com');
        self::assertNotEmpty($rtn);
        self::assertContains('no route for /testm/test/sub', $rtn);
    }

    public function testURL() {
        self::assertEquals('http://login.wulaphp.com:9090/test/add', App::url('login/test/add'));
        self::assertEquals('http://login.wulaphp.com:9090/test/add', App::action('login\controllers\TestController::add'));
        self::assertEquals('/testm/add', App::url('testm/add'));
        self::assertEquals('/testm/test/add', App::action('\testm\controllers\TestController::add'));
    }

    private function httpGet($url, $host) {
        if (!($sock = fsockopen('127.0.0.1', 9090)))
            return false;
        stream_set_timeout($sock, 0, 250000);
        $packets   = [];
        $packets[] = 'GET ' . $url . ' HTTP/1.0';
        $packets[] = 'Host: ' . $host . ':9090';
        $packets[] = CLRF;
        $rtn       = http_send($sock, $packets, $size);
        @fclose($sock);

        return $rtn;
    }
}