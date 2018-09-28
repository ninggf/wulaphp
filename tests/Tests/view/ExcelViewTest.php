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

class ExcelViewTest extends TestCase {

    public function testRender() {
        $data = [];

        $content = excel('testm/views/index/excel', $data)->render();
        self::assertEquals('hello wula!', $content);
    }
}