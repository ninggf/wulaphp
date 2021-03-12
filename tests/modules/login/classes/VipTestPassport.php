<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login\classes;

use wulaphp\auth\Passport;

class VipTestPassport extends Passport {
    protected function doAuth($data = null): bool {
        $this->uid      = 1;
        $this->username = 'test admin';
        $this->nickname = 'test';
        $this->status   = 1;
        $this->data     = [];

        return true;
    }

    protected function verifyPasswd(string $password): bool {
        return $password == '123';
    }
}