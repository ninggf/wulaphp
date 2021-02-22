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

use wulaphp\util\Params;

class LoginParams extends Params {
    /**
     * @required
     */
    public $username;
    /**
     * @required
     */
    public $password;
    /**
     * @equalTo (password) => {password1.not.equal}
     */
    public $password1;
}