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
use wulaphp\db\DatabaseConnection;
use wulaphp\db\MetaTable;
use wulaphp\db\Table;

class MetaTableTest extends MysqlTestCase {
    protected static function initDatabase(DatabaseConnection $db): bool {
        $sql = <<< SQL
CREATE TABLE IF NOT EXISTS `user_meta` (
    `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(45) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`user_id`,`name`)
)  ENGINE=INNODB DEFAULT CHARACTER SET=UTF8
SQL;

        return $db->exec($sql);
    }

    public function testSimple() {
        $userMeta = new UserMetaModel(self::$con);
        $rst      = $userMeta->setMeta(1, 'test', 'hello');
        self::assertTrue($rst);
        $meta = $userMeta->getMeta(1, 'test');
        self::assertEquals('hello', $meta);

        $rst = $userMeta->setMeta(1, 'test', 'hello world');
        self::assertTrue($rst);
        $meta = $userMeta->getMeta(1, 'test');
        self::assertEquals('hello world', $meta);

        $metas = $userMeta->getMeta(1);
        self::assertEquals(1, count($metas));
    }

    /**
     * @depends testSimple
     */
    public function testJsonValue() {
        $userMeta = new UserMetaModel(self::$con);
        $rst      = $userMeta->setMeta(1, 'json', ['name' => 'admin', 'id' => 1]);
        self::assertTrue($rst);
        $meta = $userMeta->getJsonMeta(1, 'json');
        self::assertIsArray($meta);
        self::assertArrayHasKey('name', $meta);
        self::assertEquals('admin', $meta['name']);

        $metas = $userMeta->getMeta(1);
        self::assertEquals(2, count($metas));
    }
}

class UserMetaModel extends Table {
    use MetaTable;
}