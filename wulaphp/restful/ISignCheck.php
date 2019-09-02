<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\restful;

interface ISignCheck {
    /**
     * 签名.
     *
     * @param array  $args      参数
     * @param string $appSecret 与appId相对应的appSecret。
     * @param string $type      签名方法
     * @param bool   $server    是否是服务端器调用.
     *
     * @return string 签名
     */
    public function sign(array $args, string $appSecret, string $type = 'sha1', bool $server = false): string;
}