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

abstract class PgTestCase extends TestCase {
    /**
     * @var \wulaphp\db\DatabaseConnection
     */
    protected static $con;
    protected static $dbname;
    /**
     * @var \PDO
     */
    protected static $dialect;

    public final static function setUpBeforeClass() {
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
        try {
            self::$con = App::db($dbcfg);
            if (!static::initDatabase(self::$con)) {
                throw new \Exception('cannot init database');
            }
        } catch (\Exception $e) {
            self::rmDb(self::$dbname);
            self::assertNotNull(null, $e->getMessage());
        }
    }

    public final static function tearDownAfterClass() {
        if (self::$con) {
            self::$con->close();
            self::rmDb(self::$dbname);
        }
    }

    private static function rmDb($dbname) {
        try {
            self::$dialect->exec("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '$dbname' AND pid <> pg_backend_pid()");
            self::$dialect->exec('drop database ' . $dbname);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }
    }

    protected abstract static function initDatabase(DatabaseConnection $db): bool;
}