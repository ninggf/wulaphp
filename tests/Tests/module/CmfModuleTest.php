<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\module;

use cmf\CmfTestModule;
use testex\MysqlTestCase;
use wulaphp\db\DatabaseConnection;

class CmfModuleTest extends MysqlTestCase {
    protected static function initDatabase(DatabaseConnection $db): bool {
        return true;
    }

    public function testDefinedTables() {
        $module = new CmfTestModule();
        $tables = $module->getDefinedTables(self::$con->getDialect());

        self::assertCount(2, $tables['tables']);
        self::assertContains('cmf_table', $tables['tables']);
        self::assertContains('cmf_table1', $tables['tables']);
    }
}