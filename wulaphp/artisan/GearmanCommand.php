<?php
declare(ticks=1);
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\artisan;

/**
 * Trait GearmHelper
 * @package wulaphp\artisan;
 * @property $task
 * @property $shutdown
 */
trait GearmanCommand {
	protected $timeout;
	protected $host;
	protected $port;
	protected $count;

	protected function execute(/** @noinspection PhpUnusedParameterInspection */
		$options) {
		$worker = $this->worker();
		if (!$worker) {
			sleep(1);

			return;
		}
		$count = 0;//运行成功多少次后重启

		while (!$this->shutdown && $count < $this->count) {
			try {
				log_debug('getting job...', 'aaaaa');
				$rst    = @$worker->work();
				$status = $worker->returnCode();
				switch ($status) {
					case GEARMAN_SUCCESS:
						log_debug('su::::' . $rst, 'aaaaa');
						$count++;
						break;
					case GEARMAN_IO_WAIT:
						log_debug('iw::::' . $rst, 'aaaaa');
						sleep(10);
						break;
					case GEARMAN_WORK_FAIL:
						log_debug($worker->error() . '::::' . $rst, 'aaaaa');
						sleep(1);
						break;
					case GEARMAN_NO_JOBS:
						log_debug('nj::::' . $rst, 'aaaaa');
						sleep(1);
						break;
					case GEARMAN_NO_ACTIVE_FDS:
						log_debug('na::::' . $rst, 'aaaaa');
						sleep(5);
						break;
					case GEARMAN_WORKER_WAIT_TIMEOUT:
						log_debug('wwt::::' . $rst, 'aaaaa');
						sleep(1);
						break;
					case GEARMAN_WORKER_TIMEOUT_RETURN:
						log_debug('wttr::::' . $rst, 'aaaaa');
						sleep(1);
						break;
					default:
						log_debug($worker->error() . '::::' . $status, 'aaaaa');
						sleep(1);
				}
			} catch (\Exception $e) {
				log_error($e->getMessage(), 'gearman.err');
				break;
			}
		}

		$worker->unregisterAll();
		$worker = null;
	}

	/**
	 * @return \GearmanWorker
	 */
	protected abstract function worker();

	/**
	 * 初始化GearmanWorker.
	 *
	 * @param string   $func
	 * @param callable $cb
	 *
	 * @return \GearmanWorker|null
	 */
	protected function initWorker($func, $cb = null) {
		try {
			$worker = new \GearmanWorker();
			$worker->setId($this->port . '@' . posix_getpid());
			$worker->setTimeout($this->timeout * 1000);
			// Add Gearman Job Servers to Worker
			$connected = $worker->addServer($this->host, $this->port);
			if ($connected) {
				if (is_array($func)) {
					foreach ($func as $f) {
						$worker->addFunction($f[0], $f[1]);
					}
				} else if (is_callable($cb)) {
					$worker->addFunction($func, $cb);
				} else {
					unset($worker);

					return null;
				}

				return $worker;
			} else {
				log_error('Cannot connect to Gearmand Server: ' . $worker->error(), 'gearman.err');
			}
		} catch (\Exception $exception) {
			sleep(3);
			log_error($exception->getMessage(), 'gearman.err');
		}

		return null;
	}

	/**
	 * 获取一个GearmanClient.
	 *
	 * @return \GearmanClient|null
	 */
	protected function getGearmanClient() {
		try {
			$client = new \GearmanClient();
			$client->setTimeout($this->timeout * 1000);
			if ($client->addServer($this->host, $this->port)) {
				return $client;
			}
		} catch (\Exception $e) {
			log_error($e->getMessage(), 'gearman.err');
		}

		return null;
	}

	/**
	 *
	 * @param string       $job
	 * @param string|array $args
	 * @param null|string  $id
	 *
	 * @return string
	 */
	protected function doBackground($job, $args, $id = null) {
		if (is_array($args)) {
			$args = json_encode($args);
		}
		$client = $this->getGearmanClient();
		if ($client) {
			$rst = $client->doBackground($job, $args, $id);
			unset($client);

			return $rst;
		}

		return null;
	}

	protected function doHigh($job, $args, $id = null) {
		if (is_array($args)) {
			$args = json_encode($args);
		}
		$client = $this->getGearmanClient();
		if ($client) {
			$rst = $client->doHigh($job, $args, $id);
			unset($client);

			return $rst;
		}

		return null;
	}

	/**
	 *
	 * @param string       $job
	 * @param string|array $args
	 * @param null|string  $id
	 *
	 * @return string
	 */
	protected function doHighBackground($job, $args, $id = null) {
		if (is_array($args)) {
			$args = json_encode($args);
		}
		$client = $this->getGearmanClient();
		if ($client) {
			$rst = $client->doHighBackground($job, $args, $id);
			unset($client);

			return $rst;
		}

		return null;
	}
}