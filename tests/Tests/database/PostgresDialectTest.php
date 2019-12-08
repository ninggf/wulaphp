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
use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;

class PostgresDialectTest extends TestCase {
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected static $con;
    protected static $dbname;
    /**
     * @var \PDO
     */
    private static $dialect;

    public static function setUpBeforeClass() {
        $dbcfg         = [
            'driver'   => 'Postgres',
            'host'     => 'localhost',
            'dbname'   => 'postgres',
            'port'     => 5432,
            'user'     => 'postgres',
            'password' => ''
        ];
        self::$dialect = $dialect = DatabaseDialect::getDialect($dbcfg);
        self::assertNotNull($dialect);
        self::$dbname = rand_str(5, 'a-z') . '_db';
        self::assertTrue($dialect->createDatabase(self::$dbname, 'UTF8'), DatabaseDialect::$lastErrorMassge);

        $dbcfg['dbname'] = self::$dbname;
        self::$con       = App::db($dbcfg);
        self::assertNotNull(self::$con);
    }

    public function testConnect() {
        self::assertNotNull(self::$con);

        return self::$con;
    }

    public function testListDatabases() {
        $dbs = self::$con->getDialect()->listDatabases();
        self::assertNotEmpty($dbs);
        self::assertContains(self::$dbname, $dbs);
    }

    /**
     * @depends testConnect
     */
    public function testExec() {
        $sql = <<<'SQL'
CREATE TABLE {test_user} (
    id serial  NOT NULL  ,
    username VARCHAR(32) NOT NULL ,
    nickname VARCHAR(32) NULL ,
    phone VARCHAR(16) NULL ,
    email VARCHAR(128) NULL ,
    status SMALLINT  NOT NULL DEFAULT 1 ,
    hash VARCHAR(255) NOT NULL ,
    PRIMARY KEY (id),
    CONSTRAINT UDX_USERNAME UNIQUE (username)
) WITH ( OIDS = FALSE)
SQL;
        $rst = self::$con->exec($sql);
        self::assertTrue($rst, 'cannot create table: test_user for ' . self::$con->error);

        $rst = self::$con->exec("CREATE INDEX ON test_user(status)");
        self::assertTrue($rst, 'cannot create table: test_user for ' . self::$con->error);

        $sql1 = <<<'SQL'
CREATE TABLE {test_account} (
    id serial NOT NULL ,
    user_id INT  NOT NULL ,
    amount INT  NOT NULL DEFAULT 0 ,
    PRIMARY KEY (id)
) WITH ( OIDS = FALSE)
SQL;
        $rst  = self::$con->exec($sql1);
        self::assertTrue($rst, 'cannot create table: test_account');

        $sql2 = <<<SQL
CREATE TABLE {types} (
    id serial NOT NULL ,
    price FLOAT NOT NULL,
    quantity INT NOT NULL,
    amount DECIMAL(10 , 2 ) NOT NULL,
    PRIMARY KEY (id)
) WITH ( OIDS = FALSE)
SQL;
        $rst  = self::$con->exec($sql2);
        self::assertTrue($rst, 'cannot create table: types');

        return self::$con;
    }

    /**
     * @param $db
     *
     * @depends testExec
     */
    public function testSimpleTrans(DatabaseConnection $db) {
        $affected = false;
        if ($db->start()) {
            $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo', 'user100', md5('123321'));

            if ($affected) {
                $db->commit();
            } else {
                $db->rollback();
            }
        }
        self::assertTrue($affected, $db->error);
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo');
        self::assertNotEmpty($rst, var_export($rst, true));
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
        $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo2', 'user100', md5('123321'));
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
        $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo3', 'user100', md5('123321'));
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
        $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo4', 'user100', md5('123321'));
        self::assertTrue($affected, $db->error);
        $db->commit();//3
        $rst = $db->query('select * from {test_user} where username = %s LIMIT 2 offset 0', 'Leo4');
        self::assertNotEmpty($rst, $db->error);
        $db->commit();//2
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo4');
        self::assertNotEmpty($rst, $db->error);
        $db->rollback();//1,此处回滚（之前的提交都不算数）
        $rst = $db->queryOne('select * from {test_user} where username = %s', 'Leo4');
        self::assertEmpty($rst, $db->error);
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
        $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo5', 'user100', md5('123321'));
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
        $affected = $db->cudx("INSERT INTO {test_user} (username,nickname,hash) VALUES (%s,%s,%s)", 'Leo6', 'user100', md5('123321'));
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
        self::assertEquals(2, $cnt['cnt']);
        $rst = $db->queryOne('select * from {test_user} where username Like %s LIMIT %d', 'Leo%', 1);
        self::assertNotEmpty($rst);
        $rst = $db->queryOne('select * from {test_user} where username Like %s LIMIT 1 offset %d', 'Leo%', 1);
        self::assertNotEmpty($rst);
        $rst = $db->queryOne('select * from {test_user} where username Like %s LIMIT 1 offset 0', 'Leo%');
        self::assertNotEmpty($rst);
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
        $data['amount']   = '2.5';
        $t                = $db->insert($data)->into('types')->newId();

        self::assertTrue(!!$t);
        $rdata = $db->queryOne('select * from types where id = ' . $t);
        self::assertEquals(1.25, $rdata['price']);
        self::assertEquals(2, $rdata['quantity']);
        self::assertEquals(2.50, $rdata['amount']);

        $data['price']    = '1.25';
        $data['quantity'] = '4';
        $data['amount']   = 5;
        $q                = $db->insert($data)->into('types');
        $t                = $q->newId();
        $sql              = $q->getSqlString();
        self::assertTrue(!!$t);
        self::assertEquals('INSERT INTO types (price,quantity,amount) VALUES (\'1.25\' , \'4\' , 5)', $sql);

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
        self::assertEquals('INSERT INTO types (price,quantity,amount) VALUES (\'abc\' , 2 , 2.4)', $sql);
        self::assertNotTrue(!!$t, $t . ' is the new id');
        self::assertNotEmpty($err);
        self::assertContains('invalid input syntax', $err);

        $data['price']    = '0,1,1),(1';
        $data['quantity'] = 2;
        $data['amount']   = 2.4;
        $q                = $db->insert($data)->into('types');
        $t                = $q->newId();
        $err              = $q->lastError();
        $sql              = $q->getSqlString();
        self::assertNotTrue(!!$t, $t);
        self::assertEquals('INSERT INTO types (price,quantity,amount) VALUES (\'0,1,1),(1\' , 2 , 2.4)', $sql);
        self::assertNotEmpty($err);
        self::assertContains('invalid input syntax', $err);
    }

    public static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->close();
            $dbname = self::$dbname;
            self::$dialect->exec("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '$dbname' AND pid <> pg_backend_pid()");
            self::$dialect->exec('drop database ' . self::$dbname);
        }
    }
}