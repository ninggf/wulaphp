<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\tests\Tests;

use PHPUnit\Framework\TestCase;
use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;

/**
 * Class DatabaseConnectionTest
 * @package wula\tests\Tests
 * @group   mysql
 */
class DatabaseConnectionTest extends TestCase {
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected static $con;
    protected static $dbname;

    public static function setUpBeforeClass() {
        $dbcfg   = [
            'driver'   => 'MySQL',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => ''
        ];
        $dialect = DatabaseDialect::getDialect($dbcfg);
        self::assertNotNull($dialect);
        self::$dbname = rand_str(5, 'a-z') . '_db';
        self::assertTrue($dialect->createDatabase(self::$dbname, 'UTF8MB4'), DatabaseDialect::$lastErrorMassge);
        $dialect->close();

        $dbcfg['dbname'] = self::$dbname;
        self::$con       = App::db($dbcfg);
        self::assertNotNull(self::$con);

        $sql = <<<'SQL'
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
        $rst = self::$con->exec($sql);
        self::assertTrue($rst, 'cannot create table: test_user for ' . self::$con->error);

        $sql1 = <<<'SQL'
CREATE TABLE `{test_account}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户余额',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8 COMMENT '用户账户'
SQL;
        $rst  = self::$con->exec($sql1);
        self::assertTrue($rst, 'cannot create table: test_account');

        $sql2 = <<<SQL
CREATE TABLE `{types}` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `price` FLOAT NOT NULL,
    `quantity` INT NOT NULL,
    `amount` DECIMAL(10 , 2 ) NOT NULL,
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARACTER SET=UTF8 COMMENT '类型测试'
SQL;
        $rst  = self::$con->exec($sql2);
        self::assertTrue($rst, 'cannot create table: types');

        $rst = self::$con->exec(' SET SESSION sql_mode = \'STRICT_TRANS_TABLES\'');
        self::assertTrue($rst, 'cannot set sql_mode');
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
        self::assertNotEmpty($rst, $db->error);
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
        $rst = $db->queryOne('select * from {test_user} where username = %s LIMIT 0,2', 'Leo4');
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
     *
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

    /**
     * @param $db
     *
     * @depends testConnect
     * @depends testTransparentTrans5
     */
    public function testLimitRegex(DatabaseConnection $db) {
        $cnt = $db->queryOne('select count(*) as cnt from {test_user} where username Like %s', 'Leo%');
        self::assertNotEmpty($cnt, var_export($cnt, true));
        $sql   = <<<SQL
select 
    count(*) as cnt 
from 
    {test_user} 
where 
    username Like %s
SQL;
        $limit = $db->getDialect()->getLimit($sql, 0, 1);
        self::assertEquals(' LIMIT 0 , 1', $limit);
        $cnt = $db->queryOne($sql, 'Leo%');
        self::assertNotEmpty($cnt, var_export($cnt, true));
        self::assertEquals(2, $cnt['cnt']);

        $rst = $db->queryOne('select * from {test_user} where username Like %s LIMIT 1', 'Leo%');
        self::assertNotEmpty($rst);
        self::assertEquals('Leo', $rst['username']);

        $rst = $db->queryOne('select * from {test_user} where username Like %s LIMIT 1,1', 'Leo%');
        self::assertNotEmpty($rst);
        self::assertEquals('Leo3', $rst['username']);
        $sql   = <<<SQL
select * from {test_user} 
    where 
    username Like %s
SQL;
        $limit = $db->getDialect()->getLimit($sql, 0, 1);
        self::assertEquals(' LIMIT 0 , 1', $limit);
        $rst = $db->queryOne($sql, 'Leo%');
        self::assertNotEmpty($rst);
        self::assertEquals('Leo', $rst['username']);

        $sql   = <<<SQL
select * from {test_user} 
    where 
    username Like %s LIMIT 1,1
SQL;
        $limit = $db->getDialect()->getLimit($sql, 0, 1);
        self::assertEquals('', $limit);
        $rst = $db->queryOne($sql, 'Leo%');
        self::assertNotEmpty($rst);
        self::assertEquals('Leo3', $rst['username']);
    }

    /**
     * @param $db
     *
     * @depends testConnect
     */
    public function testTypes(DatabaseConnection $db) {
        $data['price']    = 1.2;
        $data['quantity'] = 2;
        $data['amount']   = 2.4;
        $t                = $db->insert($data)->into('types')->newId();

        self::assertTrue(!!$t);
        $rdata = $db->queryOne('select * from types where id = ' . $t);
        self::assertEquals(1.2, $rdata['price']);
        self::assertEquals(2, $rdata['quantity']);
        self::assertEquals(2.40, $rdata['amount']);

        $data['price']    = 1.25;
        $data['quantity'] = 2;
        $data['amount']   = imv('price*quantity');
        $t                = $db->insert($data)->into('types')->newId();

        self::assertTrue(!!$t);
        $rdata = $db->queryOne('select * from types where id = ' . $t);
        self::assertEquals(1.25, $rdata['price']);
        self::assertEquals(2, $rdata['quantity']);
        self::assertEquals(2.50, $rdata['amount']);

        $data['price']    = '1.25';
        $data['quantity'] = '4';
        $data['amount']   = imv('price*quantity');
        $q                = $db->insert($data)->into('types');
        $t                = $q->newId();
        $sql              = $q->getSqlString();
        self::assertTrue(!!$t);
        self::assertEquals('INSERT INTO types (`price`,`quantity`,`amount`) VALUES (\'1.25\' , \'4\' , price*quantity)', $sql);

        $rdata = $db->queryOne('select * from types where id = ' . $t);
        self::assertEquals(1.25, $rdata['price']);
        self::assertEquals(4, $rdata['quantity']);
        self::assertTrue('5.00' === $rdata['amount'], '5.00!=' . $rdata['amount']);

        $data['price']    = 'abc';
        $data['quantity'] = 2;
        $data['amount']   = 2.4;
        $q                = $db->insert($data)->into('types');
        $t                = $q->newId();
        $err              = $q->lastError();
        $sql              = $q->getSqlString();
        self::assertEquals('INSERT INTO types (`price`,`quantity`,`amount`) VALUES (\'abc\' , 2 , 2.4)', $sql);
        self::assertNotTrue(!!$t, $t . ' is the new id');
        self::assertNotEmpty($err);
        self::assertContains('\'price\' at row 1', $err);

        $data['price']    = '0,1,1),(1';
        $data['quantity'] = 2;
        $data['amount']   = 2.4;
        $q                = $db->insert($data)->into('types');
        $t                = $q->newId();
        $err              = $q->lastError();
        $sql              = $q->getSqlString();
        self::assertNotTrue(!!$t, $t);
        self::assertEquals('INSERT INTO types (`price`,`quantity`,`amount`) VALUES (\'0,1,1),(1\' , 2 , 2.4)', $sql);
        self::assertNotEmpty($err);
        self::assertContains('\'price\' at row 1', $err);
    }

    public static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->exec('drop database ' . self::$dbname);
            self::$con->close();
        }
    }
}