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
use wulaphp\util\CurlClient;

/**
 * 默认模块测试
 * @package tests\Tests\module
 */
class DefaultModuleTest extends TestCase {
    public function testDefaultModuleRoute() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090');
        $this->assertEquals('hello Woola!', $content);

        $content = $curlient->get('http://127.0.0.1:9090/wula');
        $this->assertEquals('this ia wula', $content);
    }
}