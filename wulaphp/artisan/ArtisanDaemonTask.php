<?php
declare(ticks=1);

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
	protected $logging     = false;
	const WORK_DONE_EXIT_CODE = 42;

	public function __construct() {
		parent::__construct();
		define('ARTISAN_TASK_PID', 1);
	}

	public final function run() {
		if (!function_exists('pcntl_fork')) {
			$this->error('miss pcntl extension, install it first!');
			exit(1);
		}

		$cmd     = $this->cmd();
		$options = $this->getOptions();
		if (!$this->argValid($options)) {
			exit(1);
		}
		$pid = pcntl_fork();
		if ($pid > 0) {
			//主程序退出
			exit(0);
		} else if (0 === $pid) {
			umask(0);
			$sid = posix_setsid();
			if ($sid < 0) {
				$this->error('[' . $cmd . '] Could not detach session id.');
				exit(1);
			}
			$this->setUp($options);
			fclose(STDIN);
			fclose(STDOUT);
			fclose(STDERR);

			$STDIN = @fopen('/dev/null', 'r');
			if ($this->logging) {
				if ($this->logging !== true) {
					$logf = LOGS_PATH . $this->logging;
				} else {
					$logf = LOGS_PATH . str_replace(':', '.', $cmd) . '.log';
				}
			} else {
				$logf = '/dev/null';
			}
			$STDERR = $STDOUT = @fopen($logf, is_file($logf) ? 'ab' : 'wb');

			$this->doStartLoop($options);
			$this->tearDown($options);

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
				$myid           = posix_getpid();
				$this->isParent = false;
				$this->pid      = '[' . $myid . '] ';
				$this->taskId   = $i;
				$this->initSignal();
				$this->init($options);
				$this->execute($options);
				usleep(5000);
				exit(0);
			} else {
				$this->workers[ $pid ] = $pid;
				$i++;
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

	protected function init($options) {

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
		$signals = [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU];
		foreach (array_unique($signals) as $signal) {
			pcntl_signal($signal, [$this, 'signal']);
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

	protected function argValid($options) {
		return true;
	}

	protected function setMaxMemory($size) {
		@ini_set('memory_limit', $size);
	}

	protected function execute($options) {
		while (!$this->shutdown) {
			$this->loop($options);
			usleep(10);
		}
	}

	protected abstract function loop($options);
}