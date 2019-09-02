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
use wulaphp\db\dialect\DatabaseDialect;

class QueryBuilderTest extends TestCase {
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
    }

    public static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->exec('drop database ' . self::$dbname);
            self::$con->close();
        }
    }

    public function testGetSqlString() {
        $q = self::$con->select('*')->from('`{table}` as T')->where([
            'id'     => 1,
            'name %' => 'leo%'
        ])->asc('id')->desc('name')->limit(0, 2);

        $sql = $q . '';
        self::assertEquals('SELECT * FROM `table` AS T WHERE `id` = :id_0 AND `name` LIKE :name_0 ORDER BY `id` ASC , `name` DESC LIMIT :limit_0,:limit_1', $sql);
        $sql = $q . '';
        self::assertEquals('SELECT * FROM `table` AS T WHERE `id` = :id_0 AND `name` LIKE :name_0 ORDER BY `id` ASC , `name` DESC LIMIT :limit_0,:limit_1', $sql);
        $sql = $q->getSqlString();
        self::assertEquals('SELECT * FROM `table` AS T WHERE `id` = 1 AND `name` LIKE \'leo%\' ORDER BY `id` ASC , `name` DESC LIMIT 0,2', $sql);

        $q->field(imv('a-b'), 'abd');
        $sql = $q->getSqlString();
        self::assertEquals('SELECT *,a-b AS `abd` FROM `table` AS T WHERE `id` = 1 AND `name` LIKE \'leo%\' ORDER BY `id` ASC , `name` DESC LIMIT 0,2', $sql);

        $cnt  = self::$con->select(imv('COUNT(*)', 'cnt'))->from('{item} AS IT')->where([
            'IT.tid' => imv('T.id'),
            'name'   => 'abc'
        ]);
        $sql1 = $cnt->getSqlString();
        self::assertEquals('SELECT COUNT(*) AS `cnt` FROM item AS IT WHERE `IT`.`tid` = T.id AND `name` = \'abc\'', $sql1);

        $q->field($cnt, 'cntt');
        $sql = $q . '';
        self::assertEquals('SELECT *,a-b AS `abd`,(SELECT COUNT(*) AS `cnt` FROM item AS IT WHERE `IT`.`tid` = T.id AND `name` = :name_0) AS `cntt` FROM `table` AS T WHERE `id` = :id_0 AND `name` LIKE :name_1 ORDER BY `id` ASC , `name` DESC LIMIT :limit_0,:limit_1', $sql);
        $sql = $q->getSqlString();
        self::assertEquals('SELECT *,a-b AS `abd`,(SELECT COUNT(*) AS `cnt` FROM item AS IT WHERE `IT`.`tid` = T.id AND `name` = \'abc\') AS `cntt` FROM `table` AS T WHERE `id` = 1 AND `name` LIKE \'leo%\' ORDER BY `id` ASC , `name` DESC LIMIT 0,2', $sql);
    }

    public function testUpdateGetSqlString() {
        $q   = self::$con->update('`{table}`')->set(['id' => 1, 'name' => 'leo'])->where(['id' => 2]);
        $sql = $q . '';
        self::assertEquals('UPDATE `table` AS `table` SET `id` = :id_0 , `name` = :name_0 WHERE `id` = :id_1', $sql);

        $sql = $q->getSqlString();
        self::assertEquals('UPDATE `table` AS `table` SET `id` = 1 , `name` = \'leo\' WHERE `id` = 2', $sql);

        $qb = self::$con->update('`{table}`')->set([
            [['name' => 'n1'], ['id' => 1]],
            [['name' => 'n2'], ['id' => 2]]
        ], true);

        $sql = $qb->getSqlString();
        self::assertEquals('UPDATE `table` AS `table` SET `name` = \'n1\' WHERE `id` = 1', $sql);

        $up = self::$con->update('cate_item AS CateItem')->update('{cate} AS C')->set(['CateItem.deleted' => 1]);
        $up->where(['CateItem.cid' => imv('C.id'), 'C.deleted' => 1]);

        $sql = $up . '';
        self::assertEquals('UPDATE cate_item AS CateItem , cate AS C SET `CateItem`.`deleted` = :CateItem_deleted_0 WHERE `CateItem`.`cid` = C.id AND `C`.`deleted` = :C_deleted_0', $sql);

        $sql = $up->getSqlString();
        self::assertEquals('UPDATE cate_item AS CateItem , cate AS C SET `CateItem`.`deleted` = 1 WHERE `CateItem`.`cid` = C.id AND `C`.`deleted` = 1', $sql);
    }

    public function testInsertGetSqlString() {
        $data['id']   = 1;
        $data['name'] = 'leo';
        $data['time'] = imv('from_unixtime(122222)');

        $q = self::$con->insert($data)->into('table');

        $sql = $q . '';

        self::assertEquals('INSERT INTO table (`id`,`name`,`time`) VALUES (:id_0 , :name_0 , from_unixtime(122222))', $sql);

        $sql = $q->getSqlString();
        self::assertEquals('INSERT INTO table (`id`,`name`,`time`) VALUES (1 , \'leo\' , from_unixtime(122222))', $sql);

        $d['id']   = 1;
        $d['name'] = 'n1';
        $ds[]      = $d;
        $d['id']   = 2;
        $d['name'] = 'n2';
        $ds[]      = $d;

        $q = self::$con->insert($ds, true)->into('table');

        $sql = $q . '';

        self::assertEquals('INSERT INTO table (`id`,`name`) VALUES (:id_0 , :name_0)', $sql);

        $sql = $q->getSqlString();
        self::assertEquals('INSERT INTO table (`id`,`name`) VALUES (1 , \'n1\'),(2 , \'n2\')', $sql);
    }

    public function testDeleteGetSqlString() {
        $q = self::$con->delete()->from('table')->where(['id >' => 0, 'name <>' => '']);

        $sql = $q . '';
        self::assertEquals('DELETE FROM table WHERE `id` > :id_0 AND `name` <> :name_0', $sql);

        $sql = $q->getSqlString();
        self::assertEquals('DELETE FROM table WHERE `id` > 0 AND `name` <> \'\'', $sql);

        $q1 = self::$con->delete()->from('table AS T')->where(['id >' => 0, 'name <>' => '']);
        $q1->left('item AS IT', 'IT.tid', 'T.id');

        $sql = $q1 . '';
        self::assertEquals('DELETE T FROM table AS T LEFT JOIN  item AS IT ON (`IT`.`tid`=`T`.`id`) WHERE `id` > :id_0 AND `name` <> :name_0', $sql);

        $sql = $q1->getSqlString();
        self::assertEquals('DELETE T FROM table AS T LEFT JOIN  item AS IT ON (`IT`.`tid`=`T`.`id`) WHERE `id` > 0 AND `name` <> \'\'', $sql);
    }
}