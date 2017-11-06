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
	 * @param \Closure $callback 成功获取到锁后要执行的代码
	 * @param int      $timeout  锁多久会自动释放(默认120秒).
	 *
	 * @return bool|mixed  无法获取锁时返回false，成功获取锁后返回$callback的返回值.
	 */
	public static function nblock($lock, \Closure $callback, $timeout = 120) {
		try {
			$redis = RedisClient::getRedis();
			if ($redis) {
				$cnt = $redis->incr($lock);
				if ($cnt != 1) {
					return false;
				}
				$redis->setTimeout($lock, $timeout);
				try {
					return $callback();
				} catch (\Exception $e) {
					log_warn($e->getMessage(), 'redis_lock');

					return false;
				} finally {
					$redis->decr($lock);
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
	 * @param \Closure $callback 成功获取到锁后要执行的代码
	 * @param int      $timeout  获取锁超时
	 *
	 * @return bool|mixed 无法获取锁时返回false，成功获取锁后返回$callback的返回值.
	 */
	public static function lock($lock, \Closure $callback, $timeout = 30) {
		try {
			$redis = RedisClient::getRedis();
			if ($redis) {
				$start = time();
				$cnt   = $redis->incr($lock);
				while ($cnt != 1) {
					usleep(500);
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
				try {
					return $callback();
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
}