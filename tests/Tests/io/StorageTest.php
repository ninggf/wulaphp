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
use wulaphp\io\Storage;

class StorageTest extends TestCase {

    public function testStorage() {
        $storage = new Storage('file:path=storage/test');

        $rst = $storage->save('hello.txt', 'hello world!');
        $this->assertTrue($rst !== false);

        $content = $storage->load('hello.txt');

        $this->assertNotEmpty($content);
        $this->assertEquals('hello world!', $content);

        $rst = $storage->delete('hello.txt');
        $this->assertTrue($rst);
    }

    /**
     * @expectedException \Exception
     */
    public function testUnknowDriver() {
        $storage = new Storage('filex:path=storage/test');
    }

    public function testSSDBStorage() {
        $storage = new Storage('ssdb:host=127.0.0.1;port=8888;timeout=5');

        $rst = $storage->save('hello.txt', 'hello world!');
        $this->assertTrue($rst !== false);

        $content = $storage->load('hello.txt');

        $this->assertNotEmpty($content);
        $this->assertEquals('hello world!', $content);

        $rst = $storage->delete('hello.txt');
        $this->assertTrue($rst);
    }
}