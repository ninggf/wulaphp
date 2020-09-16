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
        $url    = 'http://127.0.0.1:9090/admin';

        $client->get($url, function ($ch, $data) use (&$rtn) {
            preg_match('/^admin(.+?)ok$/', $data, $m);
            $rtn = $m[1];

            return 0;
        });

        self::assertNotEmpty($rtn);
    }
}