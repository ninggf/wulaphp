<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\util;

use PHPUnit\Framework\TestCase;
use wulaphp\util\CurlClient;

class CurlientTest extends TestCase {
    public function testJsonBody() {
        $client       = CurlClient::getClient(20);
        $data['name'] = 'Woola';
        $data['age']  = 18;

        $rst = $client->post('http://127.0.0.1:9090/testm/json', $data, true);
        self::assertEquals('{"name":"Woola","age":18}', $rst);
    }

    public function testWrite() {
        $client = CurlClient::getClient(20);
        $url    = 'http://46.push2.eastmoney.com/api/qt/stock/sse?ut=fa5fd1943c7b386f172d6893dbfba10b&fltt=2&fields=f50,f48,f117&secid=0.002703';

        $client->get($url, function ($ch, $data) use (&$rtn) {
            preg_match('/^data: (.+)/', $data, $m);
            $rtn = @json_decode($m[1], true);

            return 0;
        });

        self::assertNotEmpty($rtn);
    }
}