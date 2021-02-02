<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\modules\testm\model;

use wulaphp\db\Table;

class AccountModel extends Table {
    protected function user(): array {
        return $this->belongsTo('user', 'user_id');
    }
}