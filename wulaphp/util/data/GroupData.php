<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util\data;
/**
 * 分组数据源。
 * @package wulaphp\util\data
 */
class GroupData extends Source {
    private $size = 10;
    private $source;

    /**
     * GroupData constructor.
     *
     * @param \wulaphp\util\data\Source $source 上游数据源
     * @param int                       $size   分组大小
     */
    public function __construct(Source $source, int $size) {
        $this->source = $source;
        $this->size   = $size <= 0 ? 1 : $size;
    }

    protected function data(): \Generator {
        $gp = [];
        $i  = 0;
        foreach ($this->source->data() as $data) {
            $gp[] = $data;
            if ((++ $i) % $this->size == 0) {
                yield $gp;
                $i  = 0;
                $gp = [];
            }
        }
        if ($gp) {
            yield $gp;
        }
    }

    protected function onSinked($data) {
        # 将组内数据提交给上游数据源下做下沉处理.
        foreach ($data as $d) {
            parent::onSinked($d);
        }
    }
}