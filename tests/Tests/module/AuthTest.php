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

class AuthTest extends TestCase {

    public function testLogin() {
        $rst = $this->httpGet('http://127.0.0.1:9090/');
        self::assertEquals('test admin is logined', $rst, $rst);

        // 正常未锁屏
        $rst = $this->httpGet('http://127.0.0.1:9090/view/');
        self::assertEquals('haha you got it', $rst, $rst);

        // 锁屏
        $rst = $this->httpGet('http://127.0.0.1:9090/lock');
        self::assertEquals('locked', $rst, $rst);

        // 锁屏后访问
        $rst = $this->httpGet('http://127.0.0.1:9090/view/');
        self::assertEquals('you are locked!', $rst);

        // 解锁屏
        $rst = $this->httpGet('http://127.0.0.1:9090/unlock?passwd=123');
        self::assertEquals('unlocked', $rst, $rst);

        // 解锁屏后访问
        $rst = $this->httpGet('http://127.0.0.1:9090/view/');
        self::assertEquals('haha you got it', $rst, $rst);
    }

    private function httpGet($url) {
        if (!($sock = fsockopen('127.0.0.1', 9090)))
            return false;
        stream_set_timeout($sock, 0, 250000);
        $packets   = [];
        $packets[] = 'GET ' . $url . ' HTTP/1.0';
        $packets[] = 'Host:  login.wulaphp.com:9090';
        $packets[] = CLRF;
        $rtn       = http_send($sock, $packets, $size);
        @fclose($sock);

        return $rtn;
    }
}