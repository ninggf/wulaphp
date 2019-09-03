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

class UploadSupportTest extends TestCase {

    public function testUpload() {
        $client       = CurlClient::getClient(20);
        $data['file'] = new \CURLFile(STORAGE_PATH . 'a.txt');

        $rst = $client->post('http://127.0.0.1:9090/testm/upload', $data);
        self::assertContains('"done":1', $rst);
        self::assertContains('"path":"abc/test.txt"', $rst);

        $content = file_get_contents(TMP_PATH . 'abc/test.txt');
        self::assertEquals('a.txt', $content);

        rmdirs(TMP_PATH . 'abc', false);
    }
}