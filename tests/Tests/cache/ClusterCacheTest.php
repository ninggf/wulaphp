<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\cache;

use PHPUnit\Framework\TestCase;
use wulaphp\cache\RedisCache;
use wulaphp\cache\RtCache;
use wulaphp\conf\ClusterConfiguration;
use wulaphp\conf\Configuration;

class ClusterCacheTest extends TestCase {
    public static function setUpBeforeClass() {
        define('RUN_IN_CLUSTER', 1);
        bind('on_load_config', function (Configuration $c) {
            if ($c->name() == 'cluster') {
                $c = new ClusterConfiguration();
                $c->enabled();
                $c->addRedisServer('127.0.0.1', 6379, 2, 1);

                return $c;
            }

            return $c;
        });
    }

    public function testInitRtCache() {
        $cache = RtCache::init(true);
        self::assertTrue($cache instanceof RedisCache);
    }

    /**
     * @depends testInitRtCache
     */
    public function testAdd() {
        self::assertTrue(RtCache::add('nihao', 'hello world'));
        self::assertTrue(RtCache::add('nihao1', 'hello world1'));
        self::assertTrue(RtCache::add('nihao2', 'hello world2'));

        self::assertTrue(RtCache::exists('nihao'));
        self::assertTrue(RtCache::exists('nihao1'));
        self::assertTrue(RtCache::exists('nihao2'));
    }

    /**
     * @depends testAdd
     */
    public function testGet() {
        self::assertEquals('hello world', RtCache::get('nihao'));
        self::assertEquals('hello world1', RtCache::get('nihao1'));
        self::assertEquals('hello world2', RtCache::get('nihao2'));
    }

    /**
     * @depends testGet
     */
    public function testDelete() {
        self::assertTrue(RtCache::delete('nihao'));
        self::assertTrue(!RtCache::exists('nihao'));
    }

    /**
     * @depends testDelete
     */
    public function testClear() {
        RtCache::clear();
        self::assertTrue(!RtCache::exists('nihao1'));
        self::assertTrue(!RtCache::exists('nihao2'));
    }
}