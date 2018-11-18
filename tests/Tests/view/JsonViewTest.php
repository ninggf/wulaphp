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
use wulaphp\mvc\view\JsonView;

/**
 * @package tests\Tests\view
 * @group   view
 */
class JsonViewTest extends TestCase {
    public function testRender() {
        $data    = ['name' => '牛逼的wulaphp🌥'];
        $view    = new JsonView($data);
        $content = $view->render();

        self::assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION), $content);
    }
}