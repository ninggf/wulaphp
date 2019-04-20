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
use tests\modules\login\classes\CateItemTable;
use tests\modules\login\classes\CateModel;
use wulaphp\app\App;
use wulaphp\db\dialect\DatabaseDialect;

class ModelTest extends TestCase {
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

        //以下准备测试数据.
        $sqls[] = <<< SQL
CREATE TABLE IF NOT EXISTS `cate` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `deleted` TINYINT NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_uid` INT UNSIGNED NOT NULL DEFAULT 0,
    `upid` INT UNSIGNED NOT NULL DEFAULT 0,
    `name` VARCHAR(45) NOT NULL,
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARACTER SET=UTF8
SQL;
        $sqls[] = <<< SQL
CREATE TABLE IF NOT EXISTS `cate_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `deleted` TINYINT NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_uid` INT UNSIGNED NOT NULL DEFAULT 0,
    `cid` INT UNSIGNED NOT NULL DEFAULT 0,
    `name` VARCHAR(45) NOT NULL,
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARACTER SET=UTF8
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

    public function testInsert() {
        $cate         = new CateModel(self::$con);
        $c['id']      = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C1';
        $rst          = $cate->add($c);
        self::assertEquals(1, $rst);

        $cateItem   = new CateItemTable(self::$con);
        $ci['id']   = 1;
        $ci['cid']  = 1;
        $ci['name'] = 'C1-Item1';
        $rst        = $cateItem->add($ci);
        self::assertEquals(1, $rst);
    }

    /**
     * @depends testInsert
     */
    public function testFindOne() {
        $cate = new CateModel(self::$con);
        $c    = $cate->findOne(1);
        self::assertEquals('C1', $c['name']);
    }

    /**
     * @depends testInsert
     */
    public function testInserts() {
        $c['id']      = 2;
        $c['upid']    = 0;
        $c['deleted'] = 1;
        $c['name']    = 'C2';
        $cs[]         = $c;

        $c['id']      = 3;
        $c['upid']    = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C';
        $cs[]         = $c;

        $c['id']      = 4;
        $c['upid']    = 3;
        $c['deleted'] = 0;
        $c['name']    = 'C';
        $cs[]         = $c;

        $c['id']      = 5;
        $c['upid']    = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C';
        $cs[]         = $c;

        $cate = new CateModel(self::$con);
        $rst  = $cate->adds($cs);
        self::assertTrue(is_array($rst), $cate->lastError());
        self::assertEquals(1, count($rst));
        self::assertContains(5, $rst);

        $ci['id']   = 2;
        $ci['cid']  = 2;
        $ci['name'] = 'C2-Item2';
        $cis[]      = $ci;
        $ci['id']   = 3;
        $ci['cid']  = 2;
        $ci['name'] = 'C2-Item3';
        $cis[]      = $ci;
        $ci['id']   = 4;
        $ci['cid']  = 2;
        $ci['name'] = 'C2-Item';
        $cis[]      = $ci;
        $ci['id']   = 5;
        $ci['cid']  = 3;
        $ci['name'] = 'C2-Item';
        $cis[]      = $ci;
        $ci['id']   = 6;
        $ci['cid']  = 4;
        $ci['name'] = 'C2-Item';
        $cis[]      = $ci;
        $ci['id']   = 7;
        $ci['cid']  = 5;
        $ci['name'] = 'C2-Item';
        $cis[]      = $ci;

        $cateItem = new CateItemTable(self::$con);
        $rst      = $cateItem->adds($cis);
        self::assertEquals(1, count($rst));
        self::assertContains(7, $rst);
    }

    /**
     * @depends testInserts
     */
    public function testUpdate() {
        $cate = new CateModel(self::$con);
        $rst  = $cate->updateName([3, 4, 5]);
        self::assertTrue($rst);

        $cs = $cate->find(['id @' => [3, 4, 5]], 'id,name')->toArray();
        self::assertTrue(count($cs) == 3);
        foreach ($cs as $c) {
            self::assertEquals('C' . $c['id'], $c['name']);
        }

        return $cate;
    }

    /**
     * @depends testUpdate
     */
    public function testUpdates() {
        $ci['name'] = imv('concat(name,id)');
        $wh['id']   = 4;
        $cis[]      = [$ci, $wh];
        $ci['name'] = imv('concat(name,id)');
        $wh['id']   = 5;
        $cis[]      = [$ci, $wh];
        $ci['name'] = imv('concat(name,id)');
        $wh['id']   = 6;
        $cis[]      = [$ci, $wh];
        $ci['name'] = imv('concat(name,id)');
        $wh['id']   = 7;
        $cis[]      = [$ci, $wh];

        $cateItem = new CateItemTable(self::$con);
        $rst      = $cateItem->updateNames($cis);
        self::assertEquals(4, $rst);
        $cs = $cateItem->find(['id @' => [4, 5, 6, 7]], 'id,name')->toArray();
        self::assertTrue(count($cs) == 4);
        foreach ($cs as $c) {
            self::assertEquals('C2-Item' . $c['id'], $c['name']);
        }
    }

    /**
     * @param CateModel $cate
     *
     * @depends testUpdate
     */
    public function testTree($cate) {
        $options = [];
        $cate->select()->where(['deleted' => 0])->treeKey('id')->tree($options, 'id', 'upid', 'name');
        self::assertNotEmpty($options);
        self::assertCount(4, $options);
        self::assertArrayHasKey(1, $options);
        self::assertArrayHasKey(3, $options);
        self::assertArrayHasKey(4, $options);
        self::assertArrayHasKey(5, $options);
        self::assertEquals('C1', $options['1']);
        self::assertEquals('&nbsp;&nbsp; C3', $options['3']);
        self::assertEquals('&nbsp;&nbsp;&nbsp;&nbsp; C4', $options['4']);
        self::assertEquals('&nbsp;&nbsp; C5', $options['5']);
    }

    /**
     * @param CateModel $cate
     *
     * @depends testUpdate
     */
    public function testRecurse($cate) {
        $crumbs = [['upid' => 3, 'id' => 4, 'name' => 'C4']];
        $cate->select('id,upid,name')->where(['deleted' => 0])->recurse($crumbs);
        self::assertCount(3, $crumbs);
        self::assertEquals('C1', $crumbs[0]['name']);
        self::assertEquals('C3', $crumbs[1]['name']);
        self::assertEquals('C4', $crumbs[2]['name']);
    }

    /**
     * @param CateModel $cate
     *
     * @depends testUpdate
     */
    public function testImplode($cate) {
        $cs = $cate->find(['id @' => [1, 2, 3, 4, 5], 'deleted' => 0], 'name')->implode('name', '-');
        self::assertEquals('C1-C3-C4-C5', $cs);
    }

    /**
     * @param CateModel $cate
     *
     * @depends testUpdate
     * @return CateModel
     */
    public function testToArray1($cate) {
        $cs = $cate->find(['id @' => [1, 2, 3, 4, 5], 'deleted' => 0], 'id,name')->toArray();
        self::assertCount(4, $cs);

        $cs = $cate->find(['id @' => [1, 2, 3, 4, 5], 'deleted' => 0], 'id,name')->toArray(null, 'id');
        self::assertEquals('C1', $cs[1]['name']);
        self::assertEquals('C3', $cs[3]['name']);
        self::assertEquals('C4', $cs[4]['name']);
        self::assertEquals('C5', $cs[5]['name']);

        $cs = $cate->find(['id @' => [1, 2, 3, 4, 5], 'deleted' => 0], 'id,name')->toArray('name', 'id');
        self::assertEquals('C1', $cs[1]);
        self::assertEquals('C3', $cs[3]);
        self::assertEquals('C4', $cs[4]);
        self::assertEquals('C5', $cs[5]);

        return $cate;
    }

    /**
     * @param CateModel $cate
     *
     * @depends testToArray1
     */
    public function testToArray2($cate) {
        $cs = $cate->find([
            'id @'    => [1, 2, 3, 4, 5],
            'deleted' => 0
        ], 'id,name')->alterArrayByKey('id', function ($data) {
            $data['name'] = $data['name'] . ' A';

            return $data;
        });
        self::assertEquals('C1 A', $cs[1]['name']);
        self::assertEquals('C3 A', $cs[3]['name']);
        self::assertEquals('C4 A', $cs[4]['name']);
        self::assertEquals('C5 A', $cs[5]['name']);

        $cs = $cate->find(['id @' => [1, 2, 3, 4, 5], 'deleted' => 0], 'id,name')->alterArray('name', function ($var) {
            return $var . ' B';
        });

        self::assertEquals('C1 B', $cs[0]);
        self::assertEquals('C3 B', $cs[1]);
        self::assertEquals('C4 B', $cs[2]);
        self::assertEquals('C5 B', $cs[3]);

        $cs = $cate->find([
            'id @'    => [1, 2, 3, 4, 5],
            'deleted' => 0
        ], 'id,name')->toArray('name', 'id', [0 => '-'], function ($var, $id) {
            return $var . ' AB ' . $id;
        });

        self::assertEquals('-', $cs[0]);
        self::assertEquals('C1 AB 1', $cs[1]);
        self::assertEquals('C3 AB 3', $cs[3]);
        self::assertEquals('C4 AB 4', $cs[4]);
        self::assertEquals('C5 AB 5', $cs[5]);
    }

    /**
     * @depends testToArray2
     * @depends testUpdates
     */
    public function testRecycle() {
        $cate = new CateModel(self::$con);
        $rst  = $cate->recycle(['id' => 5], 2);
        self::assertTrue($rst);
        $c5 = $cate->findOne(5);
        self::assertEquals(1, $c5['deleted']);
        self::assertEquals(2, $c5['update_uid']);
        self::assertTrue(time() >= $c5['update_time']);

        return $cate;
    }

    /**
     * @param CateModel $cate
     *
     * @depends testRecycle
     * @return CateItemTable
     */
    public function testCrossUpdate($cate) {
        $ci  = new CateItemTable($cate->db());
        $rst = $ci->updateByCate();
        self::assertEquals(4, $rst);

        return $ci;
    }

    /**
     * @param CateItemTable $ci
     *
     * @depends testCrossUpdate
     * @return CateItemTable
     */
    public function testAdvanceQuery($ci) {
        $gq = $ci->select('cid')->groupBy('cid')->desc('cid')->implode('cid');
        self::assertEquals('5,4,3,2,1', $gq, $ci->lastError());
        $ghq = $ci->select('cid')->groupBy('cid')->having('count(*) = 1')->desc('cid')->limit(0, 2)->implode('cid');
        self::assertEquals('5,4', $ghq, $ci->lastError());

        return $ci;
    }

    /**
     * @param CateItemTable $ci
     *
     * @depends testAdvanceQuery
     */
    public function testDelete($ci) {
        $rst = $ci->deleteRecycled();
        self::assertEquals(4, $rst);
        $cs = $ci->find(['id >' => 0], 'id')->asc('id')->implode('id');
        self::assertEquals('1,5,6', $cs);
    }
}