<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testex;

use PHPUnit\Framework\TestCase;
use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;

abstract class MysqlTestCase extends TestCase {
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected static $con;
    protected static $dbname;

    /**
     * @throws \wulaphp\db\DialectException
     */
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

        try {
            $dbcfg['dbname'] = self::$dbname;
            self::$con       = App::db($dbcfg);
            if (!static::initDatabase(self::$con)) {
                throw new \Exception('cannot init database');
            }
        } catch (\Exception $e) {
            $dialect->exec('drop database ' . self::$dbname);
            self::assertNotNull(null, $e->getMessage());
        } finally {
            $dialect->close();
        }
    }

    public static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->exec('drop database ' . self::$dbname);
            self::$con->close();
        }
    }

    protected abstract static function initDatabase(DatabaseConnection $db): bool;
}