<?php
declare(ticks = 5);

namespace wulaphp\artisan;
/**
 * 每个{@uses ArtisanDaemonTask}的子类都要声明<code>declare(ticks = 5);</code>
 *
 * @author  leo <windywany@gmail.com>
 * @package wulaphp\artisan
 * @since   1.0
 */
abstract class ArtisanDaemonTask extends ArtisanCommand {
	protected $workerCount = 1;
	protected $taskId      = 0;
	private   $isParent    = true;
	protected $shutdown    = false;
	private   $workers     = [];

	const WORK_DONE_EXIT_CODE = 42;

	public final function run() {
		if (!function_exists('pcntl_fork')) {
			$this->error('miss pcntl extension, install it first!');
			exit(1);
		}

		$cmd     = $this->cmd();
		$options = $this->getOptions();
		$pid     = pcntl_fork();
		if ($pid > 0) {
			exit(0);
		} elseif (0 === $pid) {
			umask(0);
			$mypid = posix_getpid();
			openlog('daemon-' . $cmd, LOG_PID | LOG_PERROR, LOG_USER);
			$sid = posix_setsid();
			if ($sid < 0) {
				syslog(LOG_ERR, 'Could not detach session id.');
				exit(1);
			}
			$this->setUp($options);
			fclose(STDIN);
			fclose(STDOUT);
			fclose(STDERR);

			$STDIN  = fopen('/dev/null', 'r');
			$STDOUT = fopen(LOGS_PATH . $cmd . '-' . $mypid . '.out', 'wb');
			$STDERR = fopen(LOGS_PATH . $cmd . '-' . $mypid . '.err', 'wb');

			$this->doStartLoop($options);

			$this->tearDown($options);
			@closelog();
			@fclose($STDIN);
			@fclose($STDOUT);
			@fclose($STDERR);
			exit(0);
		}

		return 0;
	}

	private function doStartLoop($options) {
		$parallel = $this->workerCount;
		$this->initSignal();
		$i = 0;
		while (count($this->workers) < $parallel) {
			$pid = pcntl_fork();
			if (0 === $pid) {
				define('ARTISAN_TASK_PID', posix_getpid());
				$this->isParent = false;
				$this->taskId   = $i;
				$this->initSignal();
				$exitCode = $this->execute($options);
				usleep(1000000);
				syslog(LOG_NOTICE, 'exit with code: ' . $exitCode);
				exit(0);
			} else {
				$i++;
				$this->workers[ $pid ] = $pid;
			}
		}

		do {
			// Check if the registered jobs are still alive
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				if (self::WORK_DONE_EXIT_CODE === pcntl_wexitstatus($status)) {
					$parallel = $this->workerCount;
				} else if ($parallel > 1) {
					$parallel = $parallel - 1;
				}
				unset($this->workers[ $pid ]);
			} else {
				usleep(1000);
			}
		} while (count($this->workers) >= $parallel);
	}

	// 准备任务
	protected function setUp(&$options) {

	}

	// 运行完成处理
	protected function tearDown(&$options) {

	}

	// 是否是单例
	protected function isSingle() {
		return false;
	}

	private function initSignal() {
		$signals = array(SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU);
		foreach (array_unique($signals) as $signal) {
			pcntl_signal($signal, array($this, 'signal'));
		}
	}

	public function signal($signal) {
		if ($this->isParent) {
			$wks = array_merge([], $this->workers);
			if ($wks) {
				foreach ($wks as $pid) {
					@posix_kill($pid, $signal);
					pcntl_signal_dispatch();
				}
			}
		} else {
			$this->shutdown = true;
		}
	}

	protected function setMaxMemory($size) {
		@ini_set('memory_limit', $size);
	}
}