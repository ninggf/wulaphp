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
use wulaphp\validator\Validator;

class CateItemTable extends Table {
    use Validator;
    /**
     * @digits
     */
    public $id;
    /**
     * @pattern (^[01]$) => 只能是0或1
     */
    public $deleted;
    /**
     * @required
     */
    public $name;

    public function add($ci) {
        return $this->insert($ci);
    }

    public function adds($cis) {
        return $this->inserts($cis);
    }

    public function updateNames($datas) {
        return $this->update()->set($datas, true)->affected();
    }

    public function updateByCate() {
        $sql = $this->update()->table('{cate} AS C')->set(['CateItem.deleted' => 1]);
        $sql->where(['CateItem.cid' => imv('C.id'), 'C.deleted' => 1]);

        return $sql->exec(null);
    }

    public function updateByCatePg() {
        $sql = $this->update()->table('{cate} AS C')->set(['deleted' => 1]);
        $sql->where(['CateItem.cid' => imv('C.id'), 'C.deleted' => 1]);

        return $sql->exec(null);
    }

    public function deleteRecycled() {
        $sql = $this->delete();
        $sql->left('{cate} AS C', 'CateItem.cid', 'C.id');
        $sql->where(['C.deleted' => 1]);

        return $sql->exec(null);
    }
}