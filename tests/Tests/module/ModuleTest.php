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

class ModuleTest extends TestCase {

    public function testModuleLoaded() {
        $module = App::getModule('testm');

        $this->assertNotNull($module);
        $this->assertEquals('模块一', $module->getName());
        $this->assertEquals('testm', $module->getNamespace());

        return $module;
    }

    /**
     * @depends testModuleLoaded
     *
     * @param \wulaphp\app\Module $module
     */
    public function testLoadFile($module) {
        $content = $module->loadFile('test.txt');
        $this->assertEquals('this is a test file', $content);
    }

    public function testBuiltModule() {
        $bm = App::getModule('app1');
        $this->assertNotNull($bm);
        $this->assertEquals('App1', $bm->getName());
        $this->assertEquals('app1', $bm->getNamespace());

        $content = $bm->loadFile('test.txt');
        $this->assertEquals('this is a test file', $content);

        @ob_start();
        try {
            App::run('app1');
        } catch (\Exception $e) {

        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('app1', $page);
    }
}