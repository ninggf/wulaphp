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
use wulaphp\io\LocaleUploader;

class UploaderTest extends TestCase {
    public function testUpload() {
        $uploader = new LocaleUploader();
        $date     = date("/Y/");
        $file     = STORAGE_PATH . 'a.txt';

        $rst = $uploader->save($file);
        $this->assertTrue(is_array($rst));
        $this->assertEquals("files{$date}a.txt", $rst['url']);
        $fp  = WEB_ROOT . "files{$date}a.txt";
        $cnt = file_get_contents($fp);
        $this->assertEquals('a.txt', $cnt);

        $rst = $uploader->save($file);
        $this->assertTrue(is_array($rst));
        $this->assertEquals("files{$date}a1.txt", $rst['url']);
        $fp  = WEB_ROOT . "files{$date}a1.txt";
        $cnt = file_get_contents($fp);
        $this->assertEquals('a.txt', $cnt);

        $file = STORAGE_PATH . 'b.txt';
        $rst  = $uploader->save($file, '@files1');
        $this->assertTrue(is_array($rst));
        $this->assertEquals("files1/b1.txt", $rst['url']);
        $fp  = WEB_ROOT . "files1/b1.txt";
        $cnt = file_get_contents($fp);
        $this->assertEquals('b.txt', $cnt);

        $file = STORAGE_PATH . 'c.txt';
        $rst  = $uploader->save($file, '~cdir');
        $this->assertTrue(is_array($rst));
        $this->assertEquals("files/cdir/c.txt", $rst['url']);
        $fp  = WEB_ROOT . "files/cdir/c.txt";
        $cnt = file_get_contents($fp);
        $this->assertEquals('c.txt', $cnt);

        $uploader1 = new LocaleUploader(STORAGE_PATH, 'd');

        $file = STORAGE_PATH . 'c.txt';
        $rst  = $uploader1->save($file, '@');
        $this->assertTrue(is_array($rst));
        $this->assertEquals("/d.txt", $rst['url']);
        $fp  = STORAGE_PATH . 'd.txt';
        $cnt = file_get_contents($fp);
        $this->assertEquals('c.txt', $cnt);
    }

    public function setUp() {
        $this->assertTrue(mkdir(WEB_ROOT . 'files'));
        $this->assertTrue(mkdir(WEB_ROOT . 'files1'));
        $this->assertTrue(touch(WEB_ROOT . 'files1' . DS . 'b.txt'));
    }

    public function tearDown() {
        @rmdirs(WEB_ROOT . 'files', false);
        @rmdirs(WEB_ROOT . 'files1', false);
        @unlink(STORAGE_PATH . 'd.txt');
    }
}