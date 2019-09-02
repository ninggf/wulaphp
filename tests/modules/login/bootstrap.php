<?php
/*
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace login;

use login\classes\VipTestPassport;
use wulaphp\app\App;
use wulaphp\app\Module;

class LoginModule extends Module {
    public function getName() {
        return '模块一';
    }

    public function getDescription() {
        return 'testm';
    }

    public function getHomePageURL() {
        return 'http://www.wulaphp.com/';
    }

    /**
     * @param \wulaphp\auth\Passport $passport
     *
     * @filter passport\newViptestPassport
     * @return \wulaphp\auth\Passport
     */
    public static function vipPassport($passport) {
        $passport = new VipTestPassport();

        return $passport;
    }
}

App::register(new LoginModule());