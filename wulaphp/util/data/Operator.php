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
 * 数据处理器.
 *
 * @package wulaphp\util\data
 */
abstract class Operator {
    /**
     * @var \wulaphp\util\data\State
     */
    protected $state;

    /**
     * 要处理的数据。
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public abstract function operate($data);

    public function setState(State $state) {
        $this->state = $state;
    }
}