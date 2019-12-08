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

use testex\MysqlTestCase;
use tests\modules\login\classes\CateModel;
use wulaphp\db\DatabaseConnection;

class MySQLUpsertTest extends MysqlTestCase {
    /**
     * @param \wulaphp\db\DatabaseConnection $db
     *
     * @return bool
     * @throws \Exception
     */
    protected static function initDatabase(DatabaseConnection $db): bool {
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

        foreach ($sqls as $sql) {
            $rst = self::$con->exec($sql);
            if ($rst) {
                throw new \Exception('cannot execute: ' . $sql);
            }
        }

        return true;
    }

    public function testInsert() {
        $cate         = new CateModel(self::$con);
        $c['id']      = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C1';
        $rst          = $cate->add($c);
        self::assertEquals(1, $rst);

        return $cate;
    }

    /**
     * @param $cate
     *
     * @depends testInsert
     * @return CateModel
     */
    public function testUpsert(CateModel $cate) {
        $c['id']      = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C1';
        $rst          = $cate->upsert($c, ['name' => 'c2']);
        self::assertTrue($rst);
        $c1 = $cate->findOne(1);
        self::assertEquals('c2', $c1['name']);

        return $cate;
    }

    /**
     * @depends testUpsert
     *
     * @param CateModel $cate
     */
    public function testUpserts(CateModel $cate) {
        $c['id']      = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C1';

        $c1['id']      = 2;
        $c1['deleted'] = 0;
        $c1['name']    = 'C2';

        $rst = $cate->upserts([$c, $c1], ['name' => imv('VALUES(name)')]);
        self::assertTrue($rst);

        $cr = $cate->findOne(1);
        self::assertEquals('C1', $cr['name']);

        $cr = $cate->findOne(2);
        self::assertEquals('C2', $cr['name']);
    }
}