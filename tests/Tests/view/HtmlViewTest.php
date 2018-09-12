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

/**
 * @group   view
 */
class HtmlViewTest extends TestCase {
    public function testRender() {
        $data['name'] = 'wula';

        $tpl = pview('testm/views/index/index', $data)->render();
        self::assertEquals('hello wula!', $tpl);
    }
}