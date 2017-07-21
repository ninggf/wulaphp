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

abstract class DaemonCrontab implements ICrontabJob {
	protected $singleton = false;
	public    $timeout   = 120;
	public    $fork      = true;

	public function __construct($singleton = false) {
		$this->singleton = $singleton;
	}

	public final function run() {
		if ($this->singleton) {
			$lock = TMP_PATH . '.cron_' . md5(get_class($this)) . '.lock';
			if (is_file($lock)) {
				$time = intval(file_get_contents($lock));
				if ($time + $this->timeout > time()) {
					log_warn(get_class($this) . ' job is running', 'crontab');

					return;
				}
			}
			if (!@file_put_contents($lock, time())) {
				log_error('cannot lock ' . get_class($this) . ' job', 'crontab');

				return;
			}
		}
		if (!$this->fork || defined('ARTISAN_TASK_PID') || !function_exists('pcntl_fork')) {
			$this->execute();
			// 执行完毕删除lock文件
			isset($lock) && @unlink($lock);
		} else {
			$pid = pcntl_fork();
			if ($pid === 0) {// session process
				umask(0);
				$sid = posix_setsid();
				if ($sid >= 0) {
					$pid = pcntl_fork();
					if (0 === $pid) {// work process
						@define('ARTISAN_TASK_PID', posix_getpid());
						$this->execute();
						// 执行完毕删除lock文件
						isset($lock) && @unlink($lock);
						exit(0);//子进程要退出
					} else {
						// session process wait for the work process exit.
						pcntl_waitpid($pid, $status);
					}
				} else {
					log_error('cannot set session for ' . get_class($this), 'crontab');
				}
				exit(0);//子进程要退出
			} else if ($pid == -1) {
				log_error('cannot fork process for ' . get_class($this), 'crontab');
			}
		}
	}

	/**
	 * 执行操作.
	 * @return mixed
	 */
	public abstract function execute();
}