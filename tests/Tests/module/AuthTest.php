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

define('CLRF', "\r\n");
define('CLRF1', "\r\n\r\n");
function http_send($sock, $packet, &$size = 0) {
    $rst = @fwrite($sock, $packet);
    if ($rst !== false && $rst > 0) {
        $i   = 100;
        $rtn = stream_get_contents($sock);
        $pos = strpos($rtn, CLRF1);
        while (!$pos && $i > 0) {//读完头部
            $i--;
            $rtn .= stream_get_contents($sock);
            $pos = strpos($rtn, CLRF1);
        }
        if (!$i) {
            return false;
        }
        if ($rtn) {
            $pos       = strpos($rtn, CLRF1);
            $headerStr = substr($rtn, 0, $pos);
            $preg      = '/Content-Length:\s+(\d+)/';
            if (preg_match($preg, $headerStr, $m)) {
                $size = $m[1];
            } else {
                return substr($rtn, $pos + strlen(CLRF1));
            }
            $rst   = substr($rtn, $pos + strlen(CLRF1));
            $size1 = strlen($rst);
            $j     = 400;
            while ($size1 < $size && $j > 0) {
                $j--;
                $rst   .= stream_get_contents($sock);
                $size1 = strlen($rst);
            }

            return $rst;
        }

        return false;
    } else {
        return false;
    }
}

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
        if (!($sock = fsockopen('127.0.0.1', 9090))) return false;
        stream_set_timeout($sock, 0, 250000);
        $packets   = [];
        $packets[] = 'GET ' . $url . ' HTTP/1.0';
        $packets[] = 'Host:  login.wulaphp.com:9090';
        $packets[] = CLRF;
        $packet    = implode(CLRF, $packets);
        $rtn       = http_send($sock, $packet, $size);
        @fclose($sock);

        return $rtn;
    }
}