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

interface ISecretCheck {
    /**
     * 根据appId返回appSecret。
     *
     * @param string $appId
     *
     * @return string
     */
    public function check(string $appId):string ;
}