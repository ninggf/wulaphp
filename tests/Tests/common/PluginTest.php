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

class PluginTest extends TestCase {
    private static $func1;
    private static $func2;

    public static function setUpBeforeClass() {
        self::$func1 = bind('fire_hook', function ($a) {
            echo 'this is ' . $a;
        }, 2);

        self::$func2 = bind('fire_hook', function ($b) {
            echo 'that is ' . $b;
        }, 1);

        bind('alter_var', function ($a) {
            return $a * 2;
        });
        bind('common\onAdd', '&\tests\Tests\common\PluginTest', 1, 2);
        bind('common.onAdd', '&\tests\Tests\common\PluginTest', 1, 2);
        bind('common/onAdd', '&\tests\Tests\common\PluginTest', 1, 2);
        bind('common-onAdd', '&\tests\Tests\common\PluginTest', 1, 2);
    }

    public function testHas() {
        self::assertTrue(has_hook('fire_hook', self::$func1) !== false);
        self::assertTrue(has_hook('fire_hook', self::$func2) !== false);
        self::assertTrue(has_hook('alter_var'));
    }

    /**
     * @depends testHas
     */
    public function testFire() {
        $content = fire('fire_hook', 'fire test');
        self::assertEquals('that is fire testthis is fire test', $content);
    }

    /**
     * @depends testHas
     */
    public function testApplyFilter() {
        $var = apply_filter('alter_var', 2);
        self::assertEquals(4, $var);
    }

    /**
     * @depends testApplyFilter
     */
    public function testUnbind() {
        unbind('fire_hook', self::$func1, 2);
        self::assertTrue(!has_hook('fire_hook', self::$func1));
        self::assertTrue(has_hook('fire_hook'));

        unbind_all('fire_hook');
        self::assertTrue(!has_hook('fire_hook'));
    }

    public function testClsFunc() {
        $var = apply_filter('common\onAdd', 1, 2);
        self::assertEquals(3, $var);
        $var = apply_filter('common.onAdd', 1, 2);
        self::assertEquals(3, $var);
        $var = apply_filter('common/onAdd', 1, 2);
        self::assertEquals(3, $var);
        $var = apply_filter('common-onAdd', 1, 2);
        self::assertEquals(3, $var);
    }

    public static function commononAdd($a, $b) {
        return $a + $b;
    }

    public static function common_onAdd($a, $b) {
        return $a + $b;
    }
}