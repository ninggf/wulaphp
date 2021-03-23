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
 * 请求参数式数据提供器。
 *
 * @package wulaphp\form\provider
 */
class ParamDataProvider extends FieldDataProvider {
    public function getData(bool $search = false) {
        if ($this->option) {
            @parse_str($this->option, $data);

            return $data ? $data : [];
        }

        return [];
    }
}