<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\database;

use PHPUnit\Framework\TestCase;
use tests\modules\testm\model\AccountModel;
use tests\modules\testm\model\ClassesModel;
use tests\modules\testm\model\RolesModel;
use tests\modules\testm\model\UserModel;
use wulaphp\app\App;
use wulaphp\db\dialect\DatabaseDialect;

class OrmTest extends TestCase {
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
        self::assertNotEmpty($dialect->createDatabase(self::$dbname, 'UTF8MB4'), DatabaseDialect::$lastErrorMassge);
        $dialect->close();

        $dbcfg['dbname'] = self::$dbname;
        self::$con       = App::db($dbcfg);
        self::assertNotNull(self::$con);

        $sqls[] = <<<SQL
CREATE TABLE `user` (
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

        $sqls[] = <<<SQL
CREATE TABLE `account` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户余额',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8 COMMENT '用户账户';
SQL;

        $sqls[] = <<<SQL
INSERT INTO
        `user`(`id`,`username`,`nickname`,`phone`,`email`,`hash`)
    VALUES
        (1,'user1','张三','13888888888','admin@abc.com',MD5('123321')),
        (2,'user2','李四','13988888888','admin@def.com',MD5('123321')),
        (3,'user3','王二','13788888888','admin@ghi.com',MD5('123321')),
        (4,'user4','韩梅梅','13688888888','admin@jkl.com',MD5('123321')),
        (5,'user5','李雷','13588888888','admin@mno.com',MD5('123321'))
SQL;

        $sqls[] = <<<SQL
insert into account(user_id,amount) values (1,1000),(2,2000)
SQL;

        $sqls[] = <<<SQL
ALTER TABLE user
ADD COLUMN cid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '班级编号'
SQL;

        $sqls[] = <<<SQL
CREATE TABLE `classes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(45) NOT NULL COMMENT '班级名称',
    `master` VARCHAR(45) DEFAULT NULL COMMENT '班主任',
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARSET=UTF8
SQL;

        $sqls[] = <<<SQL
INSERT INTO classes (id,name,master) VALUES
    (1,'小一班','小张'),(2,'小二班','小王')
SQL;
        $sqls[] = <<<SQL
UPDATE user SET cid = 1 WHERE id IN (1,2)
SQL;

        $sqls[] = <<<SQL
UPDATE user SET cid = 2 WHERE id IN (3,4,5)
SQL;

        $sqls [] = <<<SQL
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`)) ENGINE=INNODB DEFAULT CHARSET=UTF8
SQL;
        $sqls[]  = <<<SQL
CREATE TABLE `user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`)) ENGINE=INNODB DEFAULT CHARSET=UTF8
SQL;

        $sqls[] = <<<SQL
INSERT INTO roles (id,name) VALUES (1,'R1'),(2,'R2'),(3,'R3')
SQL;

        $sqls[] = <<<SQL
INSERT INTO user_roles (user_id,role_id) VALUES (1,1),(1,2),(2,2),(2,3),(3,1),(3,3),(4,1),(5,2),(5,3)
SQL;

        foreach ($sqls as $sql) {
            $rst = self::$con->exec($sql);
            self::assertTrue($rst, $sql);
        }
    }

    public static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->exec('drop database ' . self::$dbname);
            self::$con->close();
        }
    }

    public function testConnect() {
        self::assertNotNull(self::$con);

        return self::$con;
    }

    /**
     * @param \wulaphp\db\DatabaseConnection $db
     *
     * @depends testConnect
     */
    public function testHasOne($db) {
        $userTable = new UserModel($db);
        $user      = $userTable->findOne(1);
        $amount    = $user['account']['amount'];
        self::assertEquals(1000, $amount);

        $accountTable = new AccountModel($db);
        $account      = $accountTable->findOne(['user_id' => 1]);
        $username     = $account['user']['username'];
        self::assertEquals('user1', $username);

        $users = $userTable->find(['id @' => [1, 2]])->asc('id');
        self::assertEquals(2, count($users));
        $_us = [];
        foreach ($users as $user) {
            $_us[] = [$user['username'], $user['account']['amount']];
        }
        self::assertEquals('user1', $_us[0][0]);
        self::assertEquals(1000, $_us[0][1]);

        self::assertEquals('user2', $_us[1][0]);
        self::assertEquals(2000, $_us[1][1]);
    }

    /**
     * @param \wulaphp\db\DatabaseConnection $db
     *
     * @depends testConnect
     */
    public function testHasMany($db) {
        $userTable = new UserModel($db);
        $users     = $userTable->find(['id @' => [1, 3]])->asc('id');
        self::assertEquals(2, count($users));

        $_us = [];
        foreach ($users as $user) {
            $_us[] = [$user['username'], $user['classes']['name']];
        }
        self::assertEquals('user1', $_us[0][0]);
        self::assertEquals('小一班', $_us[0][1]);
        self::assertEquals('user3', $_us[1][0]);
        self::assertEquals('小二班', $_us[1][1]);

        $clsTable = new ClassesModel($db);
        $clses    = $clsTable->find(['id @' => [1, 2]])->asc('id');
        $_cs      = [];
        foreach ($clses as $cls) {
            $ss = [];
            foreach ($cls['students'] as $student) {
                $ss[] = $student['username'];
            }
            $_cs[] = [$cls['name'], $ss];
        }
        self::assertEquals('小一班', $_cs[0][0]);
        self::assertEquals('小二班', $_cs[1][0]);
        self::assertContains('user1', $_cs[0][1]);
        self::assertContains('user2', $_cs[0][1]);
        self::assertContains('user3', $_cs[1][1]);
        self::assertContains('user4', $_cs[1][1]);
        self::assertContains('user5', $_cs[1][1]);

        $clses = $clsTable->find(['id @' => [1, 2]])->asc('id');
        $_cs   = [];
        foreach ($clses as $cls) {
            $ss = [];
            foreach ($cls->students()->asc('username')->limit(0, 2) as $student) {
                $ss[] = $student['username'];
            }
            $_cs[] = [$cls['name'], $ss];
        }
        self::assertEquals('小一班', $_cs[0][0]);
        self::assertEquals('小二班', $_cs[1][0]);
        self::assertContains('user1', $_cs[0][1]);
        self::assertContains('user2', $_cs[0][1]);
        self::assertContains('user3', $_cs[1][1]);
        self::assertContains('user4', $_cs[1][1]);
        self::assertNotContains('user5', $_cs[1][1]);
    }

    /**
     * @param \wulaphp\db\DatabaseConnection $db
     *
     * @depends testConnect
     */
    public function testHasManyWith($db) {
        $userTable = new UserModel($db);
        $cnt       = \wulaphp\db\sql\Query::getSqlCount();
        $users     = $userTable->find(['id @' => [1, 2, 3, 4, 5]])->asc('id')->with('classes');
        $classes   = [];
        foreach ($users as $user) {
            $classes[] = [$user['username'], $user['classes']['name']];
        }
        $d = \wulaphp\db\sql\Query::getSqlCount() - $cnt;
        self::assertEquals(5, count($classes));
        self::assertEquals('小一班', $classes[0][1]);
        self::assertEquals('小二班', $classes[4][1]);
        //使用with可以减少执行的SQL
        self::assertEquals(2, $d);
    }

    /**
     * @param \wulaphp\db\DatabaseConnection $db
     *
     * @depends testConnect
     */
    public function testBelongsToMany($db) {
        $userTable = new UserModel($db);
        $user      = $userTable->findOne(1);

        $roles = [];
        foreach ($user['roles'] as $r) {
            $roles[] = $r['name'];
        }
        self::assertEquals(2, count($roles));
        self::assertContains('R1', $roles);
        self::assertContains('R2', $roles);

        $role = new RolesModel($db);
        $r    = $role->findOne(3);

        $users = [];

        foreach ($r['users'] as $u) {
            $users[] = $u['username'];
        }
        self::assertEquals(3, count($users));
        self::assertContains('user2', $users);
        self::assertContains('user3', $users);
        self::assertContains('user5', $users);
    }
}