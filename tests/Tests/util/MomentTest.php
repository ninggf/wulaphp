<?php

namespace tests\Tests\util;

use PHPUnit\Framework\TestCase;
use wulaphp\util\Moment;

class MomentTest extends TestCase {
    public function testMomentDateInfo() {


        $moment = Moment::parse('2021-05-31 14:09:12');
        self::assertEquals('2021-05-31', $moment->firstDayOfWeek()->day());
        self::assertEquals('2021-06-06', $moment->lastDayOfWeek()->day());

        self::assertEquals(1, $moment->dayOfWeek);

        $moment = Moment::parse('2021-06-06 23:59:59');
        self::assertEquals('2021-05-31', $moment->firstDayOfWeek()->day());
        self::assertEquals('2021-06-06', $moment->lastDayOfWeek()->day());

        self::assertEquals(7, $moment->dayOfWeek);
        self::assertEquals(22, $moment->weekOfYear);

        $moment = Moment::parse('2021-06-08 23:59:59');
        self::assertEquals(2, $moment->dayOfWeek);
        self::assertEquals(23, $moment->weekOfYear);
    }

    /**
     * @depends testMomentDateInfo
     * @throws \Exception
     */
    public function testMoment() {
        $moment = Moment::parse('2021-06-04 14:09:12');
        self::assertEquals(5, $moment->dayOfWeek);

        self::assertEquals('2021-06-04 00:00:00', $moment->begin());
        self::assertEquals('2021-06-04 23:59:59', $moment->end());

        self::assertEquals('2021-05-31', $moment->firstDayOfWeek()->day());
        self::assertEquals('2021-06-06', $moment->lastDayOfWeek()->day());

        self::assertEquals('2021-06-01', $moment->firstDayOfMonth()->day());
        self::assertEquals('2021-06-30', $moment->lastDayOfMonth()->day());

        self::assertEquals('2021-01-01', $moment->firstDayOfYear()->day());
        self::assertEquals('2021-12-31', $moment->lastDayOfYear()->day());

        # 加减年
        self::assertEquals('2022-06-04 14:09:12', $moment->addYears(1)->datetime());
        self::assertEquals('2023-06-04 14:09:12', $moment->addYears(2)->datetime());
        self::assertEquals('2020-06-04 14:09:12', $moment->addYears(- 1)->datetime());
        self::assertEquals('2019-06-04 14:09:12', $moment->addYears(- 2)->datetime());

        # 加减月
        self::assertEquals('2021-07-04 14:09:12', $moment->addMonths(1)->datetime());
        self::assertEquals('2021-12-04 14:09:12', $moment->addMonths(6)->datetime());
        self::assertEquals('2022-01-04 14:09:12', $moment->addMonths(7)->datetime());
        self::assertEquals('2021-05-04 14:09:12', $moment->addMonths(- 1)->datetime());
        self::assertEquals('2021-04-04 14:09:12', $moment->addMonths(- 2)->datetime());
        # 分钟
        self::assertEquals('2021-06-04 14:10:12', $moment->addMinutes(1)->datetime());
        self::assertEquals('2021-06-04 14:11:12', $moment->addMinutes(2)->datetime());

        # 秒
        self::assertEquals('2021-06-04 14:09:13', $moment->addSeconds(1)->datetime());
        self::assertEquals('2021-06-04 14:09:14', $moment->addSeconds(2)->datetime());
        self::assertEquals('2021-06-04 14:09:11', $moment->addSeconds(- 1)->datetime());
        self::assertEquals('2021-06-04 14:09:10', $moment->addSeconds(- 2)->datetime());

        # 自定义调整
        self::assertEquals('2021-09-06 14:09:11', $moment->modify('+3 month +2 day -1 second')->datetime());

    }

    /**
     * @depends testMoment
     */
    public function testTimezone() {
        self::assertNotEmpty(CURRENT_DEFAULT_TZ);

        $moment = Moment::parse('2021-06-04 06:42:01', 'UTC');

        self::assertEquals('2021-06-04 14:42:01', $moment->with('GMT+8')->datetime());
        self::assertEquals('2021-06-04 06:42:01', $moment->reset()->datetime());
    }

    /**
     * @depends testTimezone
     */
    public function testDelta() {
        $moment  = Moment::parse('2021-06-04 06:42:01', 'UTC');
        $moment1 = Moment::parse('2021-06-05 06:43:02', 'UTC');
        $delta   = $moment->delta($moment1, $ahead);
        self::assertTrue(!$ahead);
        self::assertEquals(1, $delta['day']);
        self::assertEquals(0, $delta['hour']);
        self::assertEquals(1, $delta['minute']);
        self::assertEquals(1, $delta['second']);

        $delta   = $moment1->delta($moment, $ahead);
        self::assertTrue($ahead);
        self::assertEquals(1, $delta['day']);
        self::assertEquals(0, $delta['hour']);
        self::assertEquals(1, $delta['minute']);
        self::assertEquals(1, $delta['second']);
    }
}