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
 * Class AnnotationTest,使用示例如下:
 *
 * ```php
 *       $i = 5 + 6
 * ```
 *
 *
 *
 * @package   tests\Tests\common
 * @testJson  name=leo&age=18&address=上海
 * @testJson1
 * {
 *      "name":"leo",
 *      "age":1,
 *      "address":"上海"
 * }
 * @testJson2 name Leo
 * @testJson2 address 上海 北京
 * @testJson2 tag
 * @int       10
 * @str       hello world
 * @strary    s,t,r
 * @mv        s
 * @mv        t
 * @mv        r
 * @b0        1
 * @b1        yes
 * @b2        on
 * @b3        true
 */
class AnnotationTest extends TestCase {
    public function testgetJsonArray() {
        $class = new self();
        $ann   = new Annotation(new \ReflectionObject($class));

        $doc  = $ann->getDoc();
        $edoc = <<< EDOC
Class AnnotationTest,使用示例如下:

```php
      \$i = 5 + 6
```
EDOC;

        self::assertEquals($edoc, $doc);

        $json = $ann->getJsonArray('testJson');
        self::assertNotEmpty($json);
        self::assertArrayHasKey('address', $json);
        self::assertEquals('上海', $json['address']);

        $json1 = $ann->getJsonArray('testJson1');
        self::assertNotEmpty($json1);
        self::assertArrayHasKey('address', $json1);
        self::assertEquals('上海', $json1['address']);

        $json2 = $ann->getJsonArray('testJson2');
        self::assertNotEmpty($json2);
        self::assertArrayHasKey('address', $json2, var_export($json2, true));
        self::assertEquals('上海 北京', $json2['address']);

        $int = $ann->getInt('int');
        self::assertTrue(10 === $int);

        $str = $ann->getString('str');
        self::assertEquals('hello world', $str);

        $strary = $ann->getArray('strary');
        self::assertEquals('s', $strary[0]);
        self::assertEquals('t', $strary[1]);
        self::assertEquals('r', $strary[2]);

        $mv = $ann->getArray('mv');
        self::assertEquals('s', $mv[0]);
        self::assertEquals('t', $mv[1]);
        self::assertEquals('r', $mv[2]);

        $mv = $ann->getMultiValues('mv');
        self::assertEquals('s', $mv[0]);
        self::assertEquals('t', $mv[1]);
        self::assertEquals('r', $mv[2]);

        $b0 = $ann->getBool('b0');
        self::assertTrue($b0);
        $b1 = $ann->getBool('b1');
        self::assertTrue($b1);
        $b2 = $ann->getBool('b2');
        self::assertTrue($b2);
        $b3 = $ann->getBool('b3');
        self::assertTrue($b3);
    }
}