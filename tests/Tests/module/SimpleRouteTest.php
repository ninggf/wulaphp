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
 * Class SimpleRouteTest
 * @package tests\Tests\route
 * @group   module
 */
class SimpleRouteTest extends TestCase {
    public function testModuleLoaded() {
        $testM = App::getModule('testm');
        self::assertNotNull($testM);
        self::assertEquals('www', PUBLIC_DIR);
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

    public function testTableRoute() {
        @ob_start();
        try {
            App::run('/testm/mul.html', ['i' => 20]);
        } catch (\Exception $e) {
            throw $e;
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('result is 200', $page);

        @ob_start();
        try {
            App::run('/testm/math/add.do');
        } catch (\Exception $e) {
            throw $e;
        }
        $page = @ob_get_clean();
        self::assertNotEmpty($page);
        self::assertEquals('result is 200', $page);
    }

    public function testBuildInServer() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/testm/math/add.do');

        $this->assertEquals('result is 200', $content);
    }

    public function testTableRouteWithParams() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/testm/mul.html?i=3');

        self::assertEquals('result is 30', $content);
    }

    public function testParamsInPath() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/m4/sub-x/a');
        self::assertEquals('sub-x-a', $content);

        $curlient->get('http://127.0.0.1:9090/m4/sub-x');
        self::assertEquals(404, $curlient->errorCode);
    }
}