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
use wulaphp\util\TreeWalker;

class TreeWalkerTest extends TestCase {

    public function testTreeWalkerBuild() {
        $data = [
            ['id' => 3, 'pid' => 1, 'name' => 'n1_3'],
            ['id' => 1, 'pid' => 0, 'name' => 'n1'],
            ['id' => 5, 'pid' => 0, 'name' => 'n5'],
            ['id' => 2, 'pid' => 6, 'name' => 'n5_6_2'],
            ['id' => 6, 'pid' => 5, 'name' => 'n5_6'],
        ];

        $node = TreeWalker::build($data);

        self::assertCount(6, $node->allNodes());
        self::assertCount(2, $node->nodes());
        self::assertCount(1, $node->nodes([1]));

        $n2 = $node->get(2);
        self::assertNotNull($n2);
        $ids   = $n2->ids();
        $idStr = implode(',', $ids);
        self::assertEquals('5,6,2', $idStr);

        $n3 = $node->get(3);
        self::assertNotNull($n3);
        $parents = $n3->parents(false);

        self::assertCount(1, $parents);
        self::assertEquals('n1', $parents[0]['name']);
    }
}