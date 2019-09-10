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
use wulaphp\artisan\GmTask;

class GearmWorkerTest extends TestCase {
    public function testGmWorker() {
        self::assertTrue(extension_loaded('gearman'));
        $gm  = new GmTask();
        $rst = $gm->doHigh('strrev', 'hello');
        self::assertEquals('olleh', $rst);
    }
}