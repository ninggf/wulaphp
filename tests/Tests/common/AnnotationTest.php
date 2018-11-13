<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\common;

use PHPUnit\Framework\TestCase;
use wulaphp\util\Annotation;

/**
 * Class AnnotationTest
 * @package  tests\Tests\common
 * @testJson name=leo&age=18&address=上海
 * @testJson1 {"name":"leo","age":1,"address":"上海"}
 */
class AnnotationTest extends TestCase {
    public function testgetJsonArray() {
        $class = new self();
        $ann   = new Annotation(new \ReflectionObject($class));
        $json  = $ann->getJsonArray('testJson');
        self::assertNotEmpty($json);
        self::assertArrayHasKey('address', $json);
        self::assertEquals('上海', $json['address']);

        $json1 = $ann->getJsonArray('testJson1');
        self::assertNotEmpty($json1);
        self::assertArrayHasKey('address', $json1);
        self::assertEquals('上海', $json1['address']);
    }
}