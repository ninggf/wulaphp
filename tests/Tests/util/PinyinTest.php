<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\util;

use PHPUnit\Framework\TestCase;
use wulaphp\util\Pinyin;

class PinyinTest extends TestCase {

    public function testConvert() {
        $fullPy = Pinyin::convert('乌拉php');
        self::assertEquals('wulaphp', $fullPy);

        $shortPy = Pinyin::convert('乌拉php', true);
        self::assertEquals('wlphp', $shortPy);
    }
}
