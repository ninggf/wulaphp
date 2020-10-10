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

use m3\user\controllers\Index;
use m3\user\controllers\Profile;
use PHPUnit\Framework\TestCase;
use wulaphp\app\App;
use wulaphp\util\CurlClient;

class AliasTest extends TestCase {
    public function testAlias() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/admin/m33/abc/');
        $this->assertEquals('abc is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m33/');
        $this->assertEquals('ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/m33/user');
        $this->assertEquals('admin/m3/user is ok', $content);

        $content = $curlient->get('http://127.0.0.1:9090/vip/m33/user/profile/read');
        $this->assertEquals('uid is 888888', $content);
    }

    public function testAliasUrl() {
        $url = App::url('m3/user');
        self::assertEquals('/m33/user', $url);
        $url = App::action(Index::class . '::index');
        self::assertEquals('/m33/user', $url);

        $url = App::action(Profile::class . '::read');
        self::assertEquals('/vip/m33/user/profile/read', $url);
        $url = App::url('@m3/user/profile/read');
        self::assertEquals('/vip/m33/user/profile/read', $url);
    }

    public function testAliasUrl2() {
        $url = App::action(\login\controllers\Index::class . '::index');
        self::assertEquals('http://login.wulaphp.com:9090/', $url);
        $url = App::action(\login\controllers\TestController::class . '::add');
        self::assertEquals('http://login.wulaphp.com:9090/test/add', $url);

        $url = App::url('login');
        self::assertEquals('http://login.wulaphp.com:9090/', $url);
        $url = App::url('login/test/add');
        self::assertEquals('http://login.wulaphp.com:9090/test/add', $url);
    }
}