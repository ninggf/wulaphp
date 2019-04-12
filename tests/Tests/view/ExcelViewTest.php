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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PHPUnit\Framework\TestCase;
use wulaphp\util\CurlClient;

class ExcelViewTest extends TestCase {

    public function setUp() {
        @unlink(STORAGE_PATH . 'test.xlsx');
    }

    public function testRender() {
        $rows[3] = [
            'A' => ['中一班', ['font' => ['size' => 14, 'color' => new Color(Color::COLOR_RED)]]],
            'B' => 31,
            'C' => '张老师',
            'D' => '宁老师',
            'E' => '王老师',
            'G' => date('Y-m-d H:i:s')
        ];
        $rows[4] = ['A' => '中七班', 'B' => 29, 'C' => '周老师', 'D' => '阮老师', 'E' => '徐老师'];
        $file    = STORAGE_PATH . 'test.xlsx';
        $rst     = excel('Test File', $rows, 'testm/views/test/class')->save($file);
        self::assertTrue($rst);

        $reader      = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($file);
        $worksheet   = $spreadsheet->getActiveSheet();
        $a3          = $worksheet->getCell('A3');
        $value       = $a3->getValue();
        self::assertEquals('中一班', $value);

        $font  = $a3->getStyle()->getFont();
        $color = $font->getColor();
        self::assertEquals(Color::COLOR_RED, $color->getARGB());

        $size = $font->getSize();
        self::assertEquals(14, $size);
    }

    public function testDownload() {
        $curlient = CurlClient::getClient(5);

        $content = $curlient->get('http://127.0.0.1:9090/testm/test/download');

        $file = STORAGE_PATH . 'down.xlsx';

        @file_put_contents($file, $content);

        $reader      = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($file);
        $worksheet   = $spreadsheet->getActiveSheet();
        $a3          = $worksheet->getCell('A3');
        $value       = $a3->getValue();
        self::assertEquals('中一班', $value);

        $font  = $a3->getStyle()->getFont();
        $color = $font->getColor();
        self::assertEquals(Color::COLOR_RED, $color->getARGB());

        $size = $font->getSize();
        self::assertEquals(14, $size);
    }

    public static function tearDownAfterClass() {
        @unlink(STORAGE_PATH . 'down.xlsx');
    }
}