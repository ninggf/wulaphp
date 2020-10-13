<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace demo;

use PHPUnit\Framework\TestCase;
use wulaphp\app\App;
use wulaphp\app\ModuleLoader;

/**
 * Class InstallTest
 * @package demo
 * @group   demo
 */
class InstallTest extends TestCase {

    public function testConstant() {
        self::assertEquals(APP_MODE,'test');
        self::assertTrue(defined('APPROOT'));
        self::assertEquals(APPROOT, realpath(__DIR__ . '/../../') . DS);
    }

    public function testModuleLoaded() {
        $loader = App::moduleLoader();
        self::assertTrue($loader instanceof ModuleLoader);
        $module = App::getModule('app');
        self::assertNotNull($module);
    }

    /**
     * @depends testConstant
     * @depends testModuleLoaded
     */
    public function testViewHomePage() {
        $module = App::getModule('app');
        self::assertNotNull($module, 'app module is not loaded');
        @ob_start();
        try {
            App::run('/');
        } catch (\Exception $e) {
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertStringContainsString('Hello wula !!', $page);
    }
}