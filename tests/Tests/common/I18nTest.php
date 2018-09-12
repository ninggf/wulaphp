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
use wulaphp\i18n\I18n;

/**
 * Class I18nTest
 * @package tests\Tests\common
 * @group   common
 */
class I18nTest extends TestCase {

    public function testLang() {
        self::assertTrue(defined('LANGUAGE'));
        self::assertEquals('en', LANGUAGE);
        $cnt = I18n::addLang(__DIR__ . '/../../../lang');
        self::assertTrue($cnt > 0, 'no item added');
    }

    /**
     * @depends testLang
     */
    public function testTranslate() {
        $str  = __('wulacms', WULA_VERSION);
        $str1 = I18n::translate('wulacms', [WULA_VERSION]);

        self::assertEquals($str, $str1);
        self::assertEquals('Powered By WulaCMS v' . WULA_VERSION, $str);
    }

    /**
     * @depends testLang
     */
    public function testTranslate1() {
        $str = __('required@validator');
        self::assertEquals('This field is required', $str);
        $str1 = I18n::translate1('required', [], '@validator');
        self::assertEquals($str, $str1);
    }

    /**
     * @depends testLang
     */
    public function testTranslate2() {
        $str = _tt('wulacms');
        self::assertEquals('Powered By WulaCMS v%s', $str);
    }

    /**
     * @depends testLang
     */
    public function testTranslate3() {
        $str = __('step@validator', 5);
        self::assertEquals('Please enter a multiple of 5.', $str);
        $str1 = I18n::translate1('step', [5], '@validator');
        self::assertEquals($str, $str1);
    }
}