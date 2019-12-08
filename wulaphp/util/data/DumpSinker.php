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
 * var_export it. so easy.
 *
 * @package wulaphp\util\data
 */
class DumpSinker extends Sinker {
    public function sink($data): bool {
        var_export($data);

        return true;
    }
}