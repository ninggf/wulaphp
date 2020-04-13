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
use wulaphp\util\LunarCalendar;

class LunarCalendarTest extends TestCase {

    public function testLunar() {
        $lunar = new LunarCalendar();

        $date = $lunar->lunar('2020-04-13');

        $lDateStr = $date['lYear'] . $date['lMonth'] . $date['lDay'];
        self::assertEquals('贰零贰零三廿一', $lDateStr);
        $cDateStr = $date['cYear'] . $date['cMonth'] . $date['cDay'];
        self::assertEquals('庚子庚辰丙戌', $cDateStr);

        $animals = $date['animals'];
        self::assertEquals('鼠', $animals);
    }
}