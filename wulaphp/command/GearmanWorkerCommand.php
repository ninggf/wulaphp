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
use wulaphp\artisan\GearmanCommand;

class GearmanWorkerCommand extends ArtisanMonitoredTask {
	use GearmanCommand;
	private $func;
	private $file;

	public function cmd() {
		return 'gearman';
	}

	public function desc() {
		return 'gearman worker mode';
	}

	protected function getOpts() {
		return [
			'h::host'    => 'Job server host',
			'p::port'    => 'Job server port',
			't::timeout' => 'Timeout in seconds',
			'n::number'  => 'Number of worker to do job(1)',
			'c::count'   => 'Number of jobs for worker to run before exiting(100)'
		];
	}

	protected function argDesc() {
		return '<function> <start|stop|help|status>';
	}

	protected function argValid($options) {
		$this->func = $this->opt(-2);
		if (empty($this->func)) {
			$this->error('give me a function please!');

			return false;
		}
		$this->file = APPROOT . 'gearman' . DS . $this->func . '.php';
		if (!is_file($this->file)) {
			$this->error($this->file . ' not found!');

			return false;
		}
		if (isset($options['p']) && !preg_match('/^([1-9]\d+)$/', $options['p'])) {
			$this->error('port must be digits');

			return false;
		}

		if (isset($options['t']) && !preg_match('/^([1-9]\d+)$/', $options['t'])) {
			$this->error('timeout must be digits');

			return false;
		}
		if (isset($options['c']) && !preg_match('/^([1-9]\d+)$/', $options['c'])) {
			$this->error('count must be digits');

			return false;
		}
		if (isset($options['n']) && !preg_match('/^([1-9]\d+)$/', $options['n'])) {
			$this->error('number must be digits');

			return false;
		}
		$this->host    = aryget('h', $options, 'localhost');
		$this->port    = aryget('p', $options, 4730);
		$this->timeout = aryget('t', $options, 5);
		$this->count   = aryget('c', $options, 100);

		return true;
	}

	protected function setUp(&$options) {
		$this->workerCount = aryget('n', $options, 1);
	}

	protected function worker() {
		$worker = $this->initWorker($this->func, [$this, 'doJob']);

		return $worker;
	}

	public function doJob($job) {
		if ($job instanceof \GearmanJob) {
			$wk = $job->workload();
		} else {
			$wk = $job;
		}
		$cmd  = PHP_BINARY;
		$args = escapeshellarg($this->file) . ' ' . escapeshellarg($wk);
		@exec($cmd . ' ' . $args, $output, $rtn);

		if ($job instanceof \GearmanJob) {
			if ($rtn) {
				$job->sendFail();

				return false;
			}
		}

		if ($output) {
			return implode("\n", $output);
		}

		return '';
	}

	protected function loop($options) {
		// NOTHING TO DO.
	}

	protected function getPidFilename($cmd) {
		if (empty($this->func)) {
			$this->help();
			exit(1);
		}
		if (!is_file($this->file)) {
			exit(1);
		}

		return $cmd . '-id-' . $this->func;
	}
}