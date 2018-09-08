<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\tests;

use PHPUnit\Framework\TestCase;
use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;

class DatabaseConnectionTest extends TestCase {
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected static $con;

    public static function setUpBeforeClass() {
        self::$con = App::db();
        $sql       = <<<'SQL'
CREATE TABLE `{test_user}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `username` VARCHAR(32) NOT NULL COMMENT '用户名',
    `nickname` VARCHAR(32) NULL COMMENT '昵称',
    `phone` VARCHAR(16) NULL COMMENT '手机号',
    `email` VARCHAR(128) NULL COMMENT '邮箱地址',
    `status` SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1正常,0禁用,2密码过期',
    `hash` VARCHAR(255) NOT NULL COMMENT '密码HASH',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_USERNAME` (`username` ASC),
    INDEX `IDX_STATUS` (`status` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET=UTF8 COMMENT='用户表'
SQL;
        $rst       = self::$con->exec($sql);
        self::assertTrue($rst, 'cannot create table: test_user');

        $sql1 = <<<'SQL'
CREATE TABLE `{test_account}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户余额',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8 COMMENT '用户账户'
SQL;
        $rst  = self::$con->exec($sql1);
        if (!$rst) {
            self::$con->exec('drop table {test_user}');
        }
        self::assertTrue($rst, 'cannot create table: test_account');
    }

    public function testConnect() {
        self::assertNotNull(self::$con);

        return self::$con;
    }

    /**
     * @param $db
     *
     * @depends testConnect
     */
    public function testSimpleTrans(DatabaseConnection $db) {
        $affected = false;
        if ($db->start()) {
            $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo', 'user100', md5('123321'));

            if ($affected) {
                $db->commit();
            } else {
                $db->rollback();
            }
        }
        self::assertTrue($affected, $db->error);
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo');
        self::assertNotEmpty($rst);
        self::assertEquals('Leo', $rst['username']);
    }

    /**
     * 测试事务的透明性
     *
     * @param $db
     *
     * @depends testConnect
     * @depends testSimpleTrans
     */
    public function testTransparentTrans1(DatabaseConnection $db) {
        $db->start();//1
        $db->start();//2
        $db->start();//3
        $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo2', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->rollback();//3
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo2');
        self::assertNotEmpty($rst);
        $db->rollback();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo2');
        self::assertNotEmpty($rst);
        $db->rollback();//1,此处真的回滚
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo2');
        self::assertEmpty($rst);
    }

    /**
     * 测试事务的透明性
     *
     * @param $db
     *
     * @depends testConnect
     * @depends testTransparentTrans1
     */
    public function testTransparentTrans2(DatabaseConnection $db) {
        $db->start();//1
        $db->start();//2
        $db->start();//3
        $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo3', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->commit();//3
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo3');
        self::assertNotEmpty($rst);
        $db->commit();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo3');
        self::assertNotEmpty($rst);
        $db->commit();//1,此处真的提交
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo3');
        self::assertNotEmpty($rst);
    }

    /**
     * 测试事务的透明性
     *
     * @param $db
     *
     * @depends testConnect
     * @depends testTransparentTrans2
     */
    public function testTransparentTrans3(DatabaseConnection $db) {
        $db->start();//1
        $db->start();//2
        $db->start();//3
        $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo4', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->commit();//3
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo4');
        self::assertNotEmpty($rst);
        $db->commit();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo4');
        self::assertNotEmpty($rst);
        $db->rollback();//1,此处回滚（之前的提交都不算数）
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo4');
        self::assertEmpty($rst);
    }

    /**
     * 测试事务的透明性
     *
     * @param $db
     *
     * @depends testConnect
     * @depends testTransparentTrans3
     */
    public function testTransparentTrans4(DatabaseConnection $db) {
        $db->start();//1
        $db->start();//2
        $db->start();//3
        $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo5', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->rollback();//3
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo5');
        self::assertNotEmpty($rst);
        $db->commit();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo5');
        self::assertNotEmpty($rst);
        $rst = $db->commit();//1,此处提交（提交会失败，因为在提交之前有回滚）
        self::assertFalse($rst);
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo5');
        self::assertEmpty($rst);
    }

    /**
     * 测试事务的透明性
     *
     * @param $db
     *
     * @depends testConnect
     * @depends testTransparentTrans4
     */
    public function testTransparentTrans5(DatabaseConnection $db) {
        $db->start();//1
        $db->start();//2
        $db->start();//3
        $affected = $db->cudx("INSERT INTO `{test_user}` (username,nickname,`hash`) VALUES (%s,%s,%s)", 'Leo6', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->commit();//3
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo6');
        self::assertNotEmpty($rst);
        $db->rollback();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo6');
        self::assertNotEmpty($rst);
        $rst = $db->commit();//1,此处提交（提交会失败，因为在提交之前有回滚）
        self::assertFalse($rst);
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo6');
        self::assertEmpty($rst);
    }

    public static function tearDownAfterClass() {
        self::$con->exec('drop table {test_user}');
        self::$con->exec('drop table {test_account}');
        self::$con->close();
    }
}