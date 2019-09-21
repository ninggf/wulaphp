<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;
/**
 * 基于Redis实现的简单的锁(利用Redis的incr操作的原子性实现).
 *
 * 使用Redis锁必须提供redis_config.php:
 *
 * ```php
 * return ['host'=>'localhost','port'=>6379,'db'=>0,'auth'=>'','timeout'=>5]
 * ```
 *
 * @package wulaphp\util
 */
class RedisLock {
    /**
     * 非阻塞锁.
     *
     * @param string   $lock     锁名
     * @param \Closure $callback 成功获取到锁后要执行的回调函数，它的参数为redis实例.
     * @param int      $timeout  锁多久会自动释放(默认120秒).
     *
     * @return bool|mixed  无法获取锁时返回false，成功获取锁后返回$callback的返回值.
     */
    public static function nblock(string $lock, \Closure $callback, int $timeout = 120) {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $cnt = $redis->incr($lock);
                if ($cnt != 1) {
                    return false;
                }
                $redis->expire($lock, $timeout);
                try {
                    return $callback(...[$redis]);
                } catch (\Exception $e) {
                    log_warn($e->getMessage(), 'redis_lock');
                } finally {
                    $redis->del($lock);
                }
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }

        return false;
    }

    /**
     * 阻塞锁.
     *
     * @param string   $lock     锁名
     * @param \Closure $callback 成功获取到锁后要执行的代码,它的第一个bool类型的参数用以标识是否是等待了其它锁,第二个参数为redis实例。
     * @param int      $timeout  获取锁超时
     *
     * @return bool|mixed 无法获取锁时返回false，成功获取锁后返回$callback的返回值.
     */
    public static function lock(string $lock, \Closure $callback, int $timeout = 30) {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $start = time();
                $cnt   = $redis->incr($lock);
                $wait  = false;
                while ($cnt != 1) {
                    usleep(200);
                    $wait = true;
                    if ((time() - $start) > $timeout) {
                        break;//超时
                    }
                    if (!$redis->exists($lock)) {//锁释放方式为删除
                        $cnt = $redis->incr($lock);
                    }
                }
                if ($cnt != 1) {
                    return false;
                }
                $redis->expire($lock, $timeout * 3);//防止死锁
                try {
                    return $callback(...[$wait, $redis]);
                } catch (\Exception $e) {
                    log_warn($e->getMessage(), 'redis_lock');

                    return false;
                } finally {
                    $redis->del($lock);
                }
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }

        return false;
    }

    /**
     * 用户锁，不自动释放，需要用户手动释放.
     *
     * @param string    $lock
     * @param int       $timeout
     * @param bool|null $wait 是否等待了锁.
     *
     * @return bool
     */
    public static function ulock(string $lock, int $timeout = 30, ?bool &$wait = null): bool {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $start = time();
                $cnt   = $redis->incr($lock);
                while ($cnt != 1) {
                    $wait = true;//等了
                    usleep(200);
                    if ((time() - $start) > $timeout) {
                        break;//超时
                    }
                    if (!$redis->exists($lock)) {//锁释放方式为删除
                        $cnt = $redis->incr($lock);
                    }
                }
                if ($cnt == 1) {
                    $redis->expire($lock, $timeout * 3);//设置超时，防止死锁

                    return true;
                }
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }

        return false;
    }

    /**
     * 用户非阻塞锁，需要用户手动释放.
     *
     * @param string $lock
     * @param int    $timeout
     *
     * @return bool
     */
    public static function unblock(string $lock, int $timeout = 5): bool {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $cnt = $redis->incr($lock);
                if ($cnt == 1) {
                    $redis->expire($lock, $timeout * 3);//设置超时，防止死锁

                    return true;
                }
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }

        return false;
    }

    /**
     * 释放锁.
     *
     * @param string $lock
     *
     * @deprecated 使用release或unlock
     */
    public static function uunlock(string $lock) {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $redis->del($lock);
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }
    }

    /**
     * 释放锁.
     *
     * @param string $lock
     */
    public static function release(string $lock) {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $redis->del($lock);
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }
    }

    /**
     * 释放锁.
     *
     * @param string $lock
     */
    public static function unlock(string $lock) {
        try {
            $redis = RedisClient::getRedis();
            if ($redis) {
                $redis->del($lock);
            }
        } catch (\Exception $e) {
            log_warn($e->getMessage(), 'redis_lock');
        }
    }
}