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
 * Class SubModuleTest
 * @package tests\Tests\module
 * @group   module
 */
class SubModuleTest extends TestCase {
    public function testSubModule1() {
        @ob_start();
        try {
            App::run('/subm/add/2/3');
        } catch (\Exception $e) {
            throw $e;
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('{"result":5}', $page);

    }

    public function testUserModule() {
        @ob_start();
        try {
            App::run('/subm/user');
        } catch (\Exception $e) {
            throw $e;
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('Hello wulaphp~', $page);
    }

    public function testtestUserAdd() {
        @ob_start();
        try {
            App::run('/subm/user/add/add-op');
        } catch (\Exception $e) {
            throw $e;
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('10', $page);
    }
}
