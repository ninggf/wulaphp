<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\util;

use PHPUnit\Framework\TestCase;
use tests\modules\login\classes\LoginParams;
use wulaphp\io\Request;

class ParamsTest extends TestCase {

    public static function setUpBeforeClass() {
        $data['username']  = 'Leo Ning';
        $data['password']  = '123';
        $data['password1'] = '321';
        Request::getInstance()->addUserData($data);
    }

    public function testGetParams() {
        $params = new LoginParams(true);
        $params->toArray($errors);

        self::assertNotEmpty($errors);
        self::assertTrue(isset($errors['password1']));
        self::assertEquals('password1 does not equal to password', $errors['password1']);

        Request::getInstance()->addUserData(['password1' => '123']);
        $params1 = new LoginParams(true);
        $data    = $params1->forn($errors1);
        self::assertNull($errors1);
        self::assertEquals('Leo Ning', $data['username']);
        self::assertEquals('123', $data['password']);

        $params1->toArray($errors4);
        self::assertNull($errors4);

        Request::getInstance()->addUserData(['id' => 'abc']);
        $params1 = new LoginParams(true);
        $params1->foru($errors2);
        self::assertNotNull($errors2);
        self::assertEquals('Please enter a valid number.', $errors2['id']);


        $params2 = new LoginParams(true);
        $params2->forn($errors3);
        self::assertNotNull($errors3);
        self::assertEquals('id must be null', $errors3['id']);
    }
}