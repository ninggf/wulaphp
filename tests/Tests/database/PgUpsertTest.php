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

use testex\PgTestCase;
use tests\modules\login\classes\CateModel;
use wulaphp\db\DatabaseConnection;

class PgUpsertTest extends PgTestCase {
    protected static function initDatabase(DatabaseConnection $db): bool {
        $sqls[] = <<< SQL
CREATE TABLE  "cate" (
    "id" serial  NOT NULL ,
    "deleted" SMALLINT NOT NULL DEFAULT 0,
    "update_time" INT  NOT NULL DEFAULT 0,
    "update_uid" INT  NOT NULL DEFAULT 0,
    "upid" INT  NOT NULL DEFAULT 0,
    "name" VARCHAR(45) NOT NULL,
    PRIMARY KEY ("id")
) WITH ( OIDS = FALSE)
SQL;
        foreach ($sqls as $sql) {
            $rst = self::$con->exec($sql);
            if (!$rst) {
                return false;
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
    public function testUpsertPrepare(CateModel $cate) {
        $stmt = self::$con->getDialect()->prepare('INSERT INTO cate (id,deleted,name) VALUES (:id_0 , :deleted_0 , :name_0) ON CONFLICT (id) DO UPDATE SET name = :name_1');
        self::assertTrue(is_object($stmt));

        $rst = $stmt->bindValue(':id_0', 1);
        self::assertTrue($rst);
        $rst = $stmt->bindValue(':deleted_0', 0);
        self::assertTrue($rst);
        $rst = $stmt->bindValue(':name_0', 'aaa');
        self::assertTrue($rst);
        $rst = $stmt->bindValue(':name_1', 'bbb');
        self::assertTrue($rst);
        $rst = $stmt->execute();
        self::assertTrue($rst);

        $c1 = $cate->findOne(1);
        self::assertEquals('bbb', $c1['name']);

        return $cate;
    }

    /**
     * @param $cate
     *
     * @depends testUpsertPrepare
     * @return CateModel
     */
    public function testUpsert(CateModel $cate) {
        $c['id']      = 1;
        $c['deleted'] = 0;
        $c['name']    = 'C1';
        $rst          = $cate->upsert($c, ['name' => 'c2']);

        self::assertTrue($rst, $cate->lastError());
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

        $rst = $cate->upserts([$c, $c1], ['name' => imv('EXCLUDED.name')]);
        self::assertTrue($rst);

        $cr = $cate->findOne(1);
        self::assertEquals('C1', $cr['name']);

        $cr = $cate->findOne(2);
        self::assertEquals('C2', $cr['name']);
    }
}