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

class TreeNode {
    private $id;
    /**
     * @var \wulaphp\util\TreeNode
     */
    private        $parent;
    private        $items = [];
    private        $data;
    private static $nodes = [];

    /**
     * 构建一个
     *
     * @param                             $id
     * @param array                       $data
     * @param \wulaphp\util\TreeNode|null $parent
     */
    public function __construct($id, array $data = [], ?TreeNode $parent = null) {
        if ($id) {
            $this->id     = $id;
            $this->data   = $data;
            $this->parent = $parent;
        } else {
            $this->id       = 0;
            $this->data     = $data;
            self::$nodes[0] = &$this;
        }
    }

    /**
     * 获取节点.
     *
     * @param $id
     *
     * @return \wulaphp\util\TreeNode|null
     */
    public function get($id): ?TreeNode {
        return self::$nodes[ $id ] ?? null;
    }

    /**
     * 节点扁平列表.
     *
     * @return array
     */
    public function allNodes(): array {
        return self::$nodes;
    }

    /**
     * 子节点.
     *
     * @param array $excludes
     *
     * @return array
     */
    public function nodes(array $excludes = []): array {
        if ($excludes) {
            $items = [];
            foreach ($this->items as $id => $item) {
                if (in_array($id, $excludes)) {
                    continue;
                }
                $items[ $id ] = $items;
            }
        } else {
            $items = $this->items;
        }

        return $items;
    }

    /**
     * 获取节点数据.
     *
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * 节点ID数组.
     *
     * @param bool $withMe
     *
     * @return int[]
     */
    public function ids(bool $withMe = true): array {
        $ids    = $withMe ? [$this->id] : [];
        $parent = $this->parent;
        while ($parent) {
            if ($parent->id) {
                array_unshift($ids, $parent->id);
                $parent = $parent->parent;
            } else {
                break;
            }
        }

        return $ids;
    }

    /**
     * 获取节点列表.
     *
     * @param bool $withMe
     *
     * @return array[]
     */
    public function parents(bool $withMe = true): array {
        $parents = $withMe ? [$this->data] : [];
        $parent  = $this->parent;
        while ($parent) {
            if ($parent->id) {
                array_unshift($parents, $parent->data);
                $parent = $parent->parent;
            } else {
                break;
            }
        }

        return $parents;
    }

    /**
     * 添加
     *
     * @param $id
     * @param $pid
     * @param $node
     *
     * @return \wulaphp\util\TreeNode
     */
    public function addNode($id, $pid, array $node): TreeNode {
        $tnode = $this->add($id, $node);
        if ($pid) {
            $pnode = $this->add($pid);
        } else {
            $pnode = $this;
        }
        $pnode->items[ $id ] = &$tnode;
        $tnode->parent       = &$pnode;

        return $tnode;
    }

    /**
     * @param       $id
     * @param array $node
     *
     * @return \wulaphp\util\TreeNode
     */
    private function add($id, array $node = []): TreeNode {
        if (isset(self::$nodes[ $id ])) {
            $tnode       = self::$nodes[ $id ];
            $tnode->data = $node;
        } else {
            $tnode              = new TreeNode($id, $node);
            self::$nodes[ $id ] = &$tnode;
        }

        return $tnode;
    }
}