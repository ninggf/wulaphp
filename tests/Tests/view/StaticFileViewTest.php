<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\view;

use PHPUnit\Framework\TestCase;
use wulaphp\util\CurlClient;

class StaticFileViewTest extends TestCase {
    public function testResJs() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/modules/testm/assets/a.js');

        self::assertEquals("alert('ok');", trim($content));
    }
}