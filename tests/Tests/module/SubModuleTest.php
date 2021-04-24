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
use wulaphp\util\CurlClient;

/**
 * Class SubModuleTest
 * @package tests\Tests\module
 * @group   module
 */
class SubModuleTest extends TestCase {
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

    public function testSubmRouter() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/subm/user/add/2');

        $this->assertEquals('result = 3', $content);

        $content = $curlient->get('http://127.0.0.1:9090/subm/user/add/2/3');

        $this->assertEquals('result = 5', $content);

        $curlient->get('http://127.0.0.1:9090/subm/user/add');

        self::assertEquals('404', $curlient->errorCode);

        $curlient->get('http://127.0.0.1:9090/subm/user/add/abc');

        self::assertEquals('500', $curlient->errorCode);
        self::assertContains('Argument #1 ($a) must be of type int, string given', $curlient->errorResponse);
    }
}
