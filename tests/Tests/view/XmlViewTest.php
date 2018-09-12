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
use wulaphp\mvc\view\XmlView;

/**
 * Class XmlViewTest
 * @package tests\Tests\view
 * @group   view
 */
class XmlViewTest extends TestCase {
    public function testRender() {
        $data['books'][] = ['book' => ['@auther' => 'Leo Ning', '#' => 'wulaphp从入门到精通']];
        $data['books'][] = ['book' => ['@auther' => '金庸', '#' => '笑傲江湖']];
        $view            = new XmlView($data, 'data');

        $content  = $view->render();
        $expected = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<data><books><book auther="Leo Ning">wulaphp从入门到精通</book><book auther="金庸">笑傲江湖</book></books></data>

EOL;

        self::assertEquals($expected, $content);
    }
}