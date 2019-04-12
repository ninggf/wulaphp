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

class ClassesModel extends Table {
    public function students() {
        return $this->hasMany('user', 'cid', 'id');
    }
}