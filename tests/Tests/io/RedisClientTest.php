<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\io;

use PHPUnit\Framework\TestCase;
use wulaphp\util\RedisClient;
use wulaphp\util\RedisLock;

class RedisClientTest extends TestCase {
    public function testInitialize() {
        $redis = RedisClient::getRedis();
        self::assertTrue($redis instanceof \Redis);

        return $redis;
    }

    /**
     * @param \Redis $redis
     *
     * @depends testInitialize
     * @return \Redis
     */
    public function testSet($redis) {
        self::assertTrue($redis->set('testK', 1));

        return $redis;
    }

    /**
     * @param \Redis $redis
     *
     * @depends testInitialize
     */
    public function testGet($redis) {
        self::assertEquals('1', $redis->get('testK'));
        $redis->del('testK');
    }

    public function testRedisLockbLock() {
        $wait1 = true;
        $rst   = RedisLock::lock('lock-abc', function ($wait, $redis) use (&$wait1) {
            $wait1 = $wait;

            return 'every this is ok';
        });
        self::assertEquals('every this is ok', $rst);
        self::assertTrue(!$wait1);
    }

    /**
     * @depends testRedisLockbLock
     */
    public function testRedisLockbLock1() {
        $locked = RedisLock::ulock('lock-abc', 2, $wait);
        self::assertTrue($locked);
        self::assertTrue(!$wait);
        $wait1 = true;
        $rst   = RedisLock::lock('lock-abc', function ($wait, $redis) use (&$wait1) {
            $wait1 = $wait;

            return 'every this is ok';
        });
        self::assertEquals('every this is ok', $rst);
        self::assertTrue($wait1);
    }

    /**
     * @depends testRedisLockbLock1
     */
    public function testRedisLockbLock2() {
        $locked = RedisLock::ulock('lock-abc', 2, $wait);
        self::assertTrue($locked);
        self::assertTrue(!$wait);
        RedisLock::release('lock-abc');
        $wait1 = true;
        $rst   = RedisLock::lock('lock-abc', function ($wait, $redis) use (&$wait1) {
            $wait1 = $wait;

            return 'every this is ok';
        });
        self::assertEquals('every this is ok', $rst);
        self::assertTrue(!$wait1);
    }

    public function testRedisLockbLock3() {
        $locked = RedisLock::ulock('lock-abc1', 2, $wait);
        self::assertTrue($locked);
        self::assertTrue(!$wait);
        $rst = RedisLock::nblock('lock-abc1', function ($redis) {
            return 'every this is ok';
        });
        self::assertNotTrue($rst);
    }

    public function testRedisLockbLock4() {
        $locked = RedisLock::ulock('lock-abc2', 2, $wait);
        self::assertTrue($locked);
        self::assertTrue(!$wait);
        $rst = RedisLock::unblock('lock-abc2');
        self::assertNotTrue($rst);
        RedisLock::release('lock-abc2');
    }
}