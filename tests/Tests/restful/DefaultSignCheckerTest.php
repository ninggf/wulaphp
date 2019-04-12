<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\restful;

use PHPUnit\Framework\TestCase;
use wulaphp\restful\DefaultSignChecker;

class DefaultSignCheckerTest extends TestCase {

    public function testSign() {
        $checker = new DefaultSignChecker();
        $sign    = $checker->sign(['a' => 1, 'b' => 2], '123', 'md5');
        $this->assertEquals('88ad068ff080f219dd150370a8bc8813', $sign);

        $sign = $checker->sign(['a' => 1, 'e' => [1, 2, 3], 'b' => 2], '123', 'md5');
        $this->assertEquals('9a41bbe538f4f4d577abfbc28556889e', $sign);

        $sign = $checker->sign(['c' => 'c', 'a' => 1, 'b' => ['b' => 2, 'a' => 1]], '123', 'md5');
        $this->assertEquals('ba672abe97f4ed587ac6fef5b0769339', $sign);

        $sign = $checker->sign([
            'c'    => 'c',
            'a'    => 1,
            'file' => '@' . STORAGE_PATH . 'a.txt',
            'b'    => ['b' => 2, 'a' => 1]
        ], '123', 'md5');
        $this->assertEquals('b0c01a5211ddc887bfd2e86f8732c7d3', $sign);
    }
}