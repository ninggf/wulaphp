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
 * 1. 一行一条数据。用=号分隔Key与Value.如：
 * "1=你好\n2=我好"
 * 将解析为 [1=>'你好',2=>'我好']
 *
 * 2. 或者用Json格式，如：
 * { "1":"你好","2":"我好"}
 *
 * @package wulaphp\form\provider
 * @since   1.0.0
 */
class LineDataProvider extends FieldDataProvider {
    public function getData(bool $search = false) {
        $options = $this->option;
        $datas   = [];
        if ($options instanceof \Closure) {
            $options = $options();
        } else if (is_callable($options)) {
            $options = call_user_func($options);
        }
        if (is_array($this->optionAry)) {
            $datas = $this->optionAry;
        } else if ($options) {
            $data = explode("\n", $options);
            foreach ($data as $default) {
                [$key, $d] = explode('=', $default);
                if ($d) {
                    $datas [ $key ] = $d;
                } else {
                    $datas [] = $key;
                }
            }
        }

        return $datas;
    }
}