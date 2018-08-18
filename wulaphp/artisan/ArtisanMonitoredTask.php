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

abstract class ArtisanMonitoredTask extends ArtisanCommand {
	protected $workerCount = 2;
	protected $shutdown    = false;
	private   $isParent    = true;
	private   $workers     = [];
	public    $returnPid   = false;
	private   $pidfile     = null;
	protected $logging     = false;

	public function __construct() {
		parent::__construct();
		define('ARTISAN_TASK_PID', 1);
	}

	protected function argDesc() {
		return '<start|stop|restart|status|help>';
	}

	public final function run() {
		if (!function_exists('pcntl_fork')) {
			$this->error('miss pcntl extension, install it first!');
			exit(1);
		}
		if (!function_exists('posix_getpid')) {
			$this->error('miss posix extension, install it first!');
			exit(1);
		}

		$cmd     = $this->cmd();
		$options = $this->getOptions();
		if (!$this->paramValid($options)) {
			exit(1);
		}
		$op = $this->getOperate();
		if (empty($op)) {
			$op = 'help';
		}
		if (!in_array($op, ['start', 'status', 'stop', 'help', 'restart']) && $op != $cmd) {
			$this->error('unkown command: ' . $this->color->str($op, 'red'));
			exit(1);
		}
		$this->pidfile = TMP_PATH . '.' . $this->getPidFilename($cmd) . '.pid';
		switch ($op) {
			case 'start':
				$argOk = $this->argValid($options);
				if (!$argOk) {
					return 1;
				}
				if (is_file($this->pidfile)) {
					$this->status($cmd);
					break;
				}
				$this->start($options, $cmd);
				break;
			case 'stop':
				$this->stop();
				break;
			case 'restart':
				$argOk = $this->argValid($options);
				if (!$argOk) {
					exit(1);
				}
				$this->stop();
				sleep(5);
				$this->start($options, $cmd);
				break;
			case 'help':
				$this->help();
				break;
			case 'status':
			default:
				$this->status($cmd);
				break;
		}

		return 0;
	}

	private function start($options, $cmd) {
		$pid = @pcntl_fork();
		if ($pid > 0) {
			//主程序退出
			$pidfile = $this->pidfile;
			@file_put_contents($pidfile, $pid . ',', FILE_APPEND);
			exit(0);
		} else if (0 === $pid) {
			umask(0);
			$sid = @posix_setsid();
			if ($sid < 0) {
				$this->error('[' . $cmd . '] Could not detach session id.');
				exit(1);
			}
			$this->setUp($options);

			@fclose(STDIN);
			@fclose(STDOUT);
			@fclose(STDERR);

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

			@fclose($STDIN);
			@fclose($STDOUT);
			@fclose($STDERR);
			exit(0);
		}
	}

	private function stop() {
		$pidfile = $this->pidfile;
		$opids   = @file_get_contents($pidfile);
		if ($opids) {
			$this->output('Stopping ...');
			@unlink($pidfile);
			$pids = explode(',', $opids);
			foreach ($pids as $pid) {
				$pid = trim($pid);
				if ($pid) {
					$this->output('stop process: ' . $pid . ' ... ', false);
					if (@posix_kill($pid, SIGTERM)) {
						@pcntl_signal_dispatch();
						@pcntl_waitpid($pid, $status);
						if (@pcntl_wifexited($status)) {
							$rtn = @pcntl_wexitstatus($status);
							$this->output($this->color->str($rtn ? 'fail' : 'done', $rtn ? 'red' : 'green'));
						} else {
							$this->output($this->color->str('unexpected exit', 'red'));
						}
					} else {
						$this->output($this->color->str('fail', 'red'));
					}
				}
			}
			$this->output('done!!');
		}
	}

	private function status($cmd) {
		$pidfile = $this->pidfile;
		$opids   = @file_get_contents($pidfile);
		if ($opids) {
			$pids = explode(',', $opids);
			$text = [];
			foreach ($pids as $pid) {
				$pid = trim($pid);
				if ($pid) {
					$text[] = "  |-- " . $pid . ' is ' . $this->color->str('Running', 'green');
				}
			}
			if ($text) {
				echo $this->color->str($cmd, 'blue'), " process:\n";
				echo implode("\n", $text), "\n";
			}
		} else {
			$this->output($this->color->str('Stopped', 'red'));
		}
	}

	public final function signal($signal) {
		$this->shutdown = true;
		if ($this->isParent) {
			$pidfile = $this->pidfile;
			@unlink($pidfile);
			$wks = array_merge([], $this->workers);
			if ($wks) {
				foreach ($wks as $pid) {
					@posix_kill($pid, $signal);
					pcntl_signal_dispatch();
				}
			}
		}
	}

	// 准备任务
	protected function setUp(/** @noinspection PhpUnusedParameterInspection */
		&$options) {
		$this->workerCount = 2;
	}

	protected function setMaxMemory($size) {
		@ini_set('memory_limit', $size);
	}

	/**
	 * 事件循环.
	 *
	 * @param array $options
	 */
	private function doStartLoop($options) {
		$parallel = $this->workerCount;
		$this->initSignal();
		do {
			while (count($this->workers) < $parallel) {
				$this->initSubproc($options);
			}
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				unset($this->workers[ $pid ]);
			}
			sleep(1);
		} while (!$this->shutdown);

		do {
			// Check if the registered jobs are still alive
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				unset($this->workers[ $pid ]);
			} else {
				sleep(1);
			}
		} while (count($this->workers) > 0);
	}

	/**
	 * 绑定中断
	 */
	private function initSignal() {
		$signals = [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU];
		foreach (array_unique($signals) as $signal) {
			pcntl_signal($signal, [$this, 'signal']);
		}
	}

	/**
	 * @param $options
	 */
	private function initSubproc($options) {
		if ($this->shutdown) {
			return;
		}
		$pid = pcntl_fork();
		if (0 === $pid) {
			$myid           = posix_getpid();
			$this->isParent = false;
			$this->pid      = '[' . $myid . '] ';
			$this->initSignal();
			$this->init($options);
			$this->execute($options);
			usleep(1000);
			exit(0);
		} else {
			$this->workers[ $pid ] = $pid;
		}
	}

	/**
	 * 子进程初始化（任务初始化）
	 *
	 * @param array $options
	 */
	protected function init($options) {

	}

	protected function execute($options) {
		while (!$this->shutdown) {
			try {
				$rst = $this->loop($options);
			} catch (\Exception $e) {
				$this->loge($e->getMessage());
				$rst = false;
				sleep(1);
			}
			if ($rst === false) {
				break;
			}
			usleep(500);
		}
	}

	/**
	 * PID 文件命名.
	 *
	 * @param string $cmd
	 *
	 * @return string
	 */
	protected function getPidFilename($cmd) {
		return str_replace(':', '-', $cmd);
	}

	protected function getOperate() {
		return $this->opt();
	}

	/**
	 * @param array $options
	 *
	 * @return bool 如果返回false,将终止当前子进程
	 */
	protected abstract function loop($options);
}