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

class UserModel extends Table {
    protected function account() {
        return $this->hasOne('account', 'user_id', 'id');
    }

    protected function classes() {
        return $this->belongsTo('classes', 'cid');
    }

    protected function roles() {
        return $this->belongsToMany('roles', 'user_roles', 'user_id', 'role_id');
    }
}