<?php

namespace wulaphp\util;

use wulaphp\conf\ConfigurationLoader;

class RedisClient {
	/**
	 * 获取一个Redis实例.
	 *
	 * @param string|array|null|int $cnf
	 *
	 * Redis服务器地址:
	 * 1. string: Redis服务器地址
	 * 2. array: 配置数组
	 *
	 * ```php
	 *  $cnf = [0=>$host,1=>$port,2=>$timeout,3=>$auth,4=>$db]
	 * ```
	 * 3. null： 从配置文件redis_config.php中读取
	 *
	 * ```php
	 *  return ['host'=>'localhost','port'=>6379,'db'=>0,'auth'=>'','timeout'=>5]
	 * ```
	 *
	 * 4. int: 从配置文件redis_config.php中读取,并将数据库替换为`$cnf`指定的库.
	 *
	 * @param string                $prefix key前缀.
	 * @param  int|null             $db     数据库，不为null时将替换配置中的数据库
	 *
	 * @return \Redis
	 * @throws \Exception when the redis extension is not installed.
	 */
	public static function getRedis($cnf = null, $db = null, $prefix = '') {
		static $instances = [];
		if (!extension_loaded('redis')) {
			throw new \Exception(__('The redis extension is not installed'));
		}
		if (is_numeric($cnf)) {
			$db  = intval($cnf);
			$cnf = null;
		}
		if ($cnf == null) {
			$loader = new ConfigurationLoader();
			$cfg    = $loader->loadConfig('redis');
			$cnf    = [
				$cfg->get('host'),
				$cfg->geti('port'),
				$cfg->geti('timeout', 5),
				$cfg->get('auth'),
				$cfg->geti('db', 0)
			];
		} else if (is_string($cnf)) {
			$cnf = [$cnf, 6379, 5, '', 0];
		}
		if (count($cnf) == 1) {
			$cnf[1] = 6379;
			$cnf[2] = 5;
			$cnf[3] = '';
			$cnf[4] = 0;
		}
		if (is_numeric($db)) {
			$cnf[4] = intval($db);
		}
		if (defined('ARTISAN_TASK_PID')) {
			$pid = ARTISAN_TASK_PID;
		} else {
			$pid = 0;
		}
		$rid = @"$pid:{$cnf[0]},$cnf[1],$cnf[4]";
		if (isset($instances[ $rid ])) {

			return $instances[ $rid ];
		}
		$redis = new \Redis ();
		if (count($cnf) > 2) {
			$rst = @$redis->connect($cnf [0], $cnf [1], $cnf [2]);
		} else {
			$rst = @$redis->connect($cnf [0], $cnf [1]);
		}
		if ($rst) {
			if (isset($cnf[3]) && $cnf[3]) {
				$rst = @$redis->auth($cnf[3]);
			}
			if (!$rst) {
				throw new \Exception(__('auth failed'));
			}
			if (isset($cnf[4]) && $cnf[4]) {
				$rst = @$redis->select($cnf[4]);
			} else {
				$rst = @$redis->select(0);
			}
			if (!$rst) {
				throw new \Exception(__('select database failed'));
			}
			if (extension_loaded('igbinary')) {
				@$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
			} else {
				@$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
			}
			if ($prefix) {
				@$redis->setOption(\Redis::OPT_PREFIX, $prefix . ':');
			}
			$instances[ $rid ] = $redis;

			return $instances[ $rid ];
		}

		return null;
	}
}