<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\auth;

use PHPUnit\Framework\TestCase;
use wulaphp\auth\AclExtraChecker;
use wulaphp\auth\Passport;

class TestPassport extends Passport {
    protected function doAuth($data = null) {
        if (is_int($data) && $data == 1) {
            $this->uid         = 1;
            $this->nickname    = 'user 1';
            $this->username    = 'user1';
            $this->data['pid'] = 1;

            return true;
        } else if (is_int($data) && $data == 2) {
            $this->uid         = 2;
            $this->nickname    = 'user 2';
            $this->username    = 'user2';
            $this->data['pid'] = 1;

            return true;
        } else if (is_array($data) && $data) {
            list($name, $pass) = $data;
            if ($name == 'aa' && $pass == 'bb') {
                $this->uid         = 3;
                $this->nickname    = 'user 3';
                $this->username    = 'aa';
                $this->data['pid'] = 1;

                return true;
            }
        }

        return false;
    }

    protected function checkAcl($op, $res, $extra) {
        if ($this->uid == 1) {
            return true;
        }
        if ($op = 'create' && $res == 'user') {
            return $this->uid == 2;
        }
        if ($op = 'create' && $res == 'site') {
            return $this->uid == 3;
        }

        return false;
    }
}

class SiteChecker extends AclExtraChecker {
    protected function doCheck(Passport $passport, $op, $extra) {
        switch ($op) {
            case 'create':
                return $extra && $extra['name'] == 'new site';
            default:
                return false;
        }
    }
}

class PassportTest extends TestCase {
    /**
     * @var Passport
     */
    protected static $passport;

    public static function setUpBeforeClass() {
        bind('passport\newVipPassport', function ($p) {
            if ($p instanceof Passport) {
                return new TestPassport();
            }

            return $p;
        });
        bind('rbac\checker\site', function ($checker) {
            $siteChecker = new SiteChecker();
            if ($checker) {
                $siteChecker->next($checker);
            }

            return $siteChecker;
        });
        self::$passport = Passport::get('vip');
    }

    public function testPassAndCheck() {
        $hash = Passport::passwd('123456');
        self::assertTrue(Passport::verify('123456', $hash));
    }

    public function testIns() {
        self::assertTrue(self::$passport instanceof TestPassport);
        self::assertEquals('vip', self::$passport->type);
    }

    /**
     * @depends testIns
     */
    public function testLogin() {
        $passport = whoami('vip');
        $login    = $passport->login(1);
        self::assertTrue($login);
        self::assertTrue($passport->isLogin);
        self::assertTrue($passport->isSuper());
        self::assertEquals('1', $passport->pid);
        self::assertTrue($passport->cando('create:user'));

        self::$passport->logout();

        $passport = whoami('vip');
        self::assertTrue(!$passport->isLogin);
        $login = $passport->login(2);
        self::assertTrue($login);
        self::assertTrue($passport->isLogin);
        self::assertTrue(!self::$passport->isSuper());
        self::assertEquals('1', $passport->pid);
        self::assertTrue($passport->cando('create:user'));
        $passport->logout();
    }

    /**
     * @depends testLogin
     */
    public function testExtraChecker() {
        $passport = whoami('vip');
        self::assertTrue(!$passport->isLogin);
        $login = $passport->login(['aa', 'bb']);
        self::assertTrue($login);
        self::assertTrue($passport->isLogin);
        self::assertTrue(!self::$passport->isSuper());
        self::assertEquals('user 3', $passport->nickname);
        self::assertEquals('1', $passport->pid);
        self::assertTrue(!$passport->cando('create:user'));

        self::assertTrue(!$passport->cando('create:site'));
        self::assertTrue($passport->cando('create:site', ['name' => 'new site']));
    }
}