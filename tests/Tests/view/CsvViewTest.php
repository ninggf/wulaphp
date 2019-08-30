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
use wulaphp\mvc\view\CsvView;

class CsvViewTest extends TestCase {
    public function testCsv() {
        $data[] = [1, 2, 3];
        $data[] = [4, 5, 6];
        $heads  = ['A', 'B', 'C'];

        $view = new CsvView($data);

        $rst = $view->render();
        $this->assertEquals("1,3,4\n4,5,6", $rst);

        $view->withHeads($heads);
        $rst = $view->render();
        $this->assertEquals("A,B,C\n1,2,3\n4,5,6", $rst);

        $rst = $view->sep("\t")->render();
        $this->assertEquals("A\tB\tC\n1\t2\t3\n4\t5\t6", $rst);
    }
}