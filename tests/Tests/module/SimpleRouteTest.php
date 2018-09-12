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

/**
 * Class SimpleRouteTest
 * @package tests\Tests\route
 * @group   module
 */
class SimpleRouteTest extends TestCase {
    public function testModuleLoaded() {
        $testM = App::getModule('testm');
        self::assertNotNull($testM);
    }

    /**
     * @depends testModuleLoaded
     */
    public function testSimpleRoute() {
        @ob_start();
        try {
            App::run('testm/test/add/2');
        } catch (\Exception $e) {

        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('3', $page);
    }
}