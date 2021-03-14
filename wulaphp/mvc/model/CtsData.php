<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\model;

use wulaphp\router\Router;

/**
 * CtsDatasource返回的数据.
 *
 * @package wulaphp\mvc\model
 */
class CtsData implements \IteratorAggregate, \Countable, \ArrayAccess {
    protected $data       = [];
    protected $total      = 0;
    protected $countTotal = 0;
    protected $dataType;
    protected $hasMore    = false;

    /**
     * CtsData constructor.
     *
     * @param array           $data
     * @param null|int|string $countTotal 总数
     */
    public function __construct($data = [], $countTotal = null) {
        $this->initData($data, $countTotal);
    }

    public function __invoke($filter) {
        if (is_callable($filter)) {
            array_walk($this->data, $filter);
        }
    }

    protected function initData($data, $countTotal) {
        $this->data = $data;
        if (is_array($data)) {
            $this->total = count($data);
            if ($this->total > 0 && !isset ($data [0])) {
                $this->dataType = 's';
            }
        }
        $this->countTotal = $countTotal;
    }

    public function offsetExists($offset) {
        if (is_numeric($offset)) {
            return isset ($this->data [ $offset ]);
        }

        return false;
    }

    public function offsetGet($offset) {
        if (is_numeric($offset) || empty ($offset)) {
            if (empty ($offset)) {
                $offset = 0;
            }

            return $this->data [ $offset ];
        } else if ($offset == 'total') {
            return $this->countTotal;
        } else if ($offset == 'data') {
            return $this->data;
        } else if ($offset == 'size') {
            return $this->total;
        } else if ($offset == 'hasMore') {
            return $this->hasMore ? true : false;
        }

        return '';
    }

    public function offsetSet($offset, $value) {
        if ($offset == 'hasMore') {
            $this->hasMore = $value;
        }
    }

    public function offsetUnset($offset) {
    }

    public function getIterator() {
        if ($this->dataType == 's') {
            return new \ArrayIterator ([$this->data]);
        } else {
            return new \ArrayIterator ($this->data);
        }
    }

    public function count() {
        return $this->total;
    }

    public function size() {
        return $this->total;
    }

    /**
     * 取用于ctv标签的数据.
     *
     * @return mixed
     */
    public function getData() {
        if ($this->dataType == 's') {
            return $this->data;
        } else if ($this->total > 0) {
            return $this->data [0];
        }

        return [];
    }

    public function toArray() {
        return $this->data;
    }

    public function getCountTotal() {
        return $this->countTotal;
    }

    public function total() {
        return $this->countTotal;
    }

    /**
     * 绘制分页.
     *
     * @param string $render
     * @param array  $options
     *
     * @return array
     */
    public final function getPageList($render, $options) {
        $info = Router::getRouter()->getParsedInfo();
        if (is_null($this->countTotal)) {
            $this->countTotal = $info->total;
        }
        if ($this->countTotal > 0) {
            $paging_data = apply_filter('on_render_paging_by_' . $render, [], $info, $options);
            if (empty ($paging_data)) {
                $paging_data = $this->getPageInfo($info, $this->countTotal, $options);
            }

            return $paging_data;
        } else {
            return [];
        }
    }

    /**
     * 取分页数据.
     *
     * @param \wulaphp\router\UrlParsedInfo $paging
     * @param string|int                    $total
     * @param array                         $args
     *
     * @return array
     */
    private function getPageInfo($paging, $total, $args) {
        $cur = $paging->page;
        $per = isset ($args ['limit']) ? intval($args ['limit']) : 10;
        if (!$per) {
            $per = 10;
        }

        $tp = ceil($total / $per); // 一共有多少页

        $pager ['total']  = $tp;
        $pager ['ctotal'] = $total;
        $pager ['first']  = '#';
        $pager ['prev']   = '#';
        $pager ['next']   = '#';
        $pager ['last']   = '#';

        if ($tp < 2) {
            return $pager;
        }

        $loop = true;
        if (isset ($args ['loop']) && empty ($args ['loop'])) {
            $loop = false;
        }
        $pages = [];
        if ($cur == 1) { // 当前在第一页
            $pager ['first'] = '#';
            $pager ['prev']  = '#';
        } else {
            $pager ['first'] = $paging->base(1);
            $pager ['prev']  = $cur == 2 ? $paging->base(1) : $paging->base($cur - 1);
        }
        // 向前后各多少页
        $pp = isset ($args ['pp']) ? intval($args ['pp']) : 10;
        $sp = $pp % 2 == 0 ? $pp / 2 : ($pp - 1) / 2;
        if ($cur <= $sp) {
            $start = 1;
            $end   = $pp;
            $end   = $end > $tp ? $tp : $end;
        } else {
            $start = $cur - $sp;
            $end   = $cur + $sp;
            if ($pp % 2 == 0) {
                $end -= 1;
            }
            if ($end >= $tp) {
                $start -= ($end - $tp);
                $start > 0 or $start = 1;
                $end = $tp;
            }
        }
        for ($i = $start; $i <= $end; $i ++) {
            if ($i == $cur) {
                $pages[ $i ] = $pager [ $i ] = '#';
            } else {
                $pages[ $i ] = $pager [ $i ] = $paging->base($i);
            }
        }
        if ($cur == $tp) {
            $pager ['next'] = '#';
            $pager ['last'] = '#';
        } else {
            $pager ['next'] = $paging->base($cur + 1);
            $pager ['last'] = $paging->base($tp);
        }
        if (!$loop) {
            $pages['pages'] = $pages;
        }

        return $pager;
    }
}