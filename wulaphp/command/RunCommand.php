<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command;

use wulaphp\artisan\ArtisanMonitoredTask;

class RunCommand extends ArtisanMonitoredTask {
	private $script;
	private $logfile;

	public function cmd() {
		return 'parallel:run';
	}

	public function desc() {
		return 'run a script in parallel mode';
	}

	protected function getOpts() {
		return [
			'n::number'  => 'Number of worker to run the script(4)',
			's::script'  => 'the script to run',
			'l::logfile' => 'log file'
		];
	}

	protected function paramValid($options) {
		$op = $this->getOperate();
		if (!$op || $op == 'help') {
			return true;
		}
		$s = $options['s'] ?? '';
		if (!$s) {
			$this->error('please give me a script to run!');

			return false;
		}

		$this->script = APPROOT . $s;
		if (!is_file($this->script)) {
			$this->error($this->script . ' not found!');

			return false;
		}

		return true;
	}

	protected function setUp(&$options) {
		$this->workerCount = aryget('n', $options, 4);
		$this->logfile     = aryget('l', $options);
	}

	protected function loop($options) {
		$cmd = escapeshellcmd(PHP_BINARY);
		$arg = escapeshellarg($this->script);
		try {
			@exec($cmd . ' ' . $arg . '  2>&1', $output, $rtn);
			if ($rtn === 2) {
				//直接返回释放资源，让父进程重开子进程
				return;
			}

			if ($rtn && $output && $this->logfile) {
				log_info($cmd . ' ' . $arg . "\n\t" . implode("\n\t", $output), $this->logfile);
			}
		} catch (\Exception $e) {
			log_info($cmd . ' ' . $arg . "\n\t" . $e->getMessage(), $this->logfile);
		}
		sleep(1);
	}

	protected function getPidFilename($cmd) {
		$fname = parent::getPidFilename($cmd);
		$file  = $this->script;

		return $fname . '-' . md5($file);
	}
}