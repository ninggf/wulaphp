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

use http\Exception\InvalidArgumentException;

/**
 * 默认的数据源管理器，啥也不做。
 * 如果需要保存数据源上数据处理状态，请重写get和save方法
 * @package wulaphp\util\data
 */
abstract class State {
    protected $name;

    /**
     * FileState constructor.
     *
     * @param string $name 管理器的名称，会做为文件名的一部分.
     *
     * @throws \InvalidArgumentException when $name is empty
     */
    public function __construct(string $name) {
        if (empty($name)) {
            throw new InvalidArgumentException('name is empty');
        }
        $this->name = $name;
    }

    /**
     * 获取状态
     * @return array
     */
    public abstract function get(): array;

    /**
     * 保存状态
     *
     * @param array $state
     *
     * @return bool
     */
    public abstract function save(array $state): bool;

    /**
     * 重置状态
     * @return bool
     */
    public function reset(): bool {
        return $this->save([]);
    }
}