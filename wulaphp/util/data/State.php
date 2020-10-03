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
 * 默认的数据源管理器，啥也不做。
 * 如果需要保存数据源上数据处理状态，请重写get和save方法
 * @package wulaphp\util\data
 */
class State {
    /**
     * 获取状态
     * @return array
     */
    public function get(): array {
        return [];
    }

    /**
     * 保存状态
     *
     * @param array $state
     *
     * @return bool
     */
    public function save(array $state): bool {
        return true;
    }

    /**
     * 重置状态
     * @return bool
     */
    public function reset(): bool {
        return $this->save([]);
    }
}