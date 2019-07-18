<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\controllers;

use PhpOffice\PhpSpreadsheet\Style\Color;
use wulaphp\cache\RtCache;
use wulaphp\mvc\controller\Controller;

class TestController extends Controller {
    public function add($i, $j = 1) {
        return pview(['i' => $i, 'j' => $j]);
    }

    public function sub($x = 0, $y = 0) {
        return ['result' => $x - $y];
    }

    public function download() {
        $rows[3] = [
            'A' => ['中一班', ['font' => ['size' => 14, 'color' => new Color(Color::COLOR_RED)]]],
            'B' => 31,
            'C' => '张老师',
            'D' => '宁老师',
            'E' => '王老师',
            'G' => date('Y-m-d H:i:s')
        ];
        $rows[4] = ['A' => '中七班', 'B' => 29, 'C' => '周老师', 'D' => '阮老师', 'E' => '徐老师'];

        return excel('班级信息表-' . date('Y-m-d-His'), $rows, 'test/class');
    }

    public function rt() {
        RtCache::ladd('rt@test', '11111');

        return ['rt' => RtCache::lget('rt@test')];
    }
}