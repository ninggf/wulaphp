<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form\provider;
/**
 * JSON格式数据提供器。
 *
 * @package wulaphp\form\provider
 */
class JsonDataProvider extends FieldDataProvider {
    public function getData(bool $search = false) {
        return (array)$this->optionAry;
    }
}