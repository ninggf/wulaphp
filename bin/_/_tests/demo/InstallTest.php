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
        self::assertEquals(realpath(__DIR__ . '/../../') . DS, APPROOT);
    }

    public function testModuleLoaded() {
        $loader = App::moduleLoader();
        self::assertTrue($loader instanceof ModuleLoader);
        $module = App::getModule('home');
        self::assertNotNull($module);
    }

    /**
     * @depends testConstant
     * @depends testModuleLoaded
     * @throws \Exception
     */
    public function testViewHomePage() {
        $module = App::getModule('home');
        self::assertNotNull($module, 'home module is not loaded');
        @ob_start();
        try {
            App::run('/');
        } catch (\Exception $e) {

        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertContains('Hello wula !!', $page);
    }
}