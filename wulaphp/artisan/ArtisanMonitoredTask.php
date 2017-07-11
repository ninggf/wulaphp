<?php
declare(ticks=5);
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
	protected $workerCount = 1;
	protected $shutdown    = false;
	private   $isParent    = true;
	private   $workers     = [];
	public    $returnPid   = false;

	protected function argDesc() {
		return '<start|stop|restart|status>';
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

		$op = $this->opt();
		switch ($op) {
			case 'start':
				$this->start($options, $cmd);
				break;
			case 'stop':
				$this->stop($cmd);
				break;
			case 'restart':
				$this->stop($cmd);
				$this->start($options, $cmd);
				break;
			case 'status':
				$this->status($cmd);
				break;
			default:
				$this->help();
		}

		return 0;
	}

	private function start($options, $cmd) {
		$pid = pcntl_fork();

		if ($pid > 0) {
			//主程序退出
			$pidfile = TMP_PATH . '.' . $cmd . '.pid';
			$opids   = @file_get_contents($pidfile);
			if ($opids) {
				$pid = $opids . ',' . $pid;
			}
			@file_put_contents($pidfile, $pid);
			exit(0);
		} elseif (0 === $pid) {
			umask(0);
			$sid = posix_setsid();
			if ($sid < 0) {
				$this->error('[' . $cmd . '] Could not detach session id.');
				exit(1);
			}
			$this->setUp($options);

			@fclose(STDIN);
			@fclose(STDOUT);
			@fclose(STDERR);

			$STDIN  = @fopen('/dev/null', 'r');
			$logf   = LOGS_PATH . str_replace(':', '.', $cmd) . '.log';
			$STDERR = $STDOUT = @fopen($logf, is_file($logf) ? 'ab' : 'wb');

			$this->doStartLoop($options);

			@fclose($STDIN);
			@fclose($STDOUT);
			exit(0);
		}
	}

	private function stop($cmd) {
		$pidfile = TMP_PATH . '.' . $cmd . '.pid';
		$opids   = @file_get_contents($pidfile);
		if ($opids) {
			@unlink($pidfile);
			$pids = explode(',', $opids);
			foreach ($pids as $pid) {
				$pid = trim($pid);
				if ($pid) {
					if (@posix_kill($pid, SIGTERM)) {
						@pcntl_signal_dispatch();
						@pcntl_waitpid($pid, $status);
					} else {
						$this->error('Cannot stop it, please kill it manually.');
					}
				}
			}
		}
	}

	private function status($cmd) {
		$pidfile = TMP_PATH . '.' . $cmd . '.pid';
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
			echo "$cmd is not run\n";
		}
	}

	public final function signal($signal) {
		$this->shutdown = true;
		if ($this->isParent) {
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
	protected function setUp(&$options) {
		$this->workerCount = 2;
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	protected function argValid($options) {
		return true;
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
			usleep(1000);
		} while (!$this->shutdown);

		do {
			// Check if the registered jobs are still alive
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				unset($this->workers[ $pid ]);
			} else {
				usleep(1000);
			}
		} while (count($this->workers) > 0);
	}

	/**
	 * 绑定中断
	 */
	private function initSignal() {
		$signals = array(SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU);
		foreach (array_unique($signals) as $signal) {
			pcntl_signal($signal, array($this, 'signal'));
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
			@define('ARTISAN_TASK_PID', posix_getpid());
			$this->isParent = false;
			$this->pid      = '[' . ARTISAN_TASK_PID . '] ';
			$this->initSignal();
			$this->init($options);
			$this->execute($options);
			usleep(5000);
			exit(0);
		} else {
			$this->workers[ $pid ] = $pid;
		}
	}

	protected function init($options) {

	}

	protected function execute($options) {
		while (!$this->shutdown) {
			$this->loop($options);
			usleep(10);
		}
	}

	protected abstract function loop($options);
}