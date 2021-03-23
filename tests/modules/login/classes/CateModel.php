<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\modules\login\classes;

use wulaphp\db\Table;

class CateModel extends Table {
    public function add($data) {
        return $this->insert($data);
    }

    public function adds($datas) {
        return $this->inserts($datas);
    }

    public function updateName($ids) {
        $data['name'] = imv('concat(name,id)');

        return $this->update($data, ['id @' => $ids]);
    }
}