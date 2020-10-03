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
 * 数据输出管理器.
 *
 * @package wulaphp\util\data
 */
abstract class Sinker {
    /**
     * @var \wulaphp\util\data\State
     */
    protected $state;

    /**
     * 输出.
     *
     * @param mixed $data
     *
     * @return bool 成功返回true,反之false.数据源可以根据此返回值对原始数据进行标记操作。
     */
    public abstract function sink($data): bool;

    /**
     * 开始
     * @return bool
     */
    public function onStarted(): bool {
        return true;
    }

    /**
     * 全部数据下沉完成.
     */
    public function onCompleted() {
    }

    /**
     * 数据源中无数据时调用。
     */
    public function noData() {
    }

    public function setState(State $state) {
        $this->state = $state;
    }
}