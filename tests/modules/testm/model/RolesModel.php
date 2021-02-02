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

use wulaphp\db\View;

class RolesModel extends View {
    protected function users() {
        return $this->belongsToMany('user', 'user_roles', 'role_id', 'user_id');
    }
}