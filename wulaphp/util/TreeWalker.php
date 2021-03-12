<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;
/**
 * 构建树型结构数据.
 *
 * @package wulaphp\util
 */
class TreeWalker {
    /**
     * @param array  $iterator
     * @param string $idf
     * @param string $pidf
     *
     * @return \wulaphp\util\TreeNode
     */
    public static function build(array $iterator, string $idf = 'id', string $pidf = 'pid'): TreeNode {
        $treeNode = new TreeNode(0);
        foreach ($iterator as $node) {
            $id  = $node[ $idf ];
            $pid = $node[ $pidf ];
            $treeNode->addNode($id, $pid, $node);
        }

        return $treeNode;
    }
}