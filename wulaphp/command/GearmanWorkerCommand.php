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
use wulaphp\artisan\GearmJob;

class GearmanWorkerCommand extends ArtisanMonitoredTask {
	use GearmanCommand;
	private $func;
	private $file;
	private $isScript = false;
	private $isJson   = false;
	/**
	 * @var GearmJob
	 */
	private $cls;

	public function cmd() {
		return 'gearman';
	}

	public function desc() {
		return 'gearman worker mode';
	}

	protected function getOpts() {
		return [
			'h::hosts'   => 'Job server hosts',
			'p::port'    => 'Job server port',
			't::timeout' => 'Timeout in seconds',
			'n::number'  => 'Number of worker to do job(1)',
			'c::count'   => 'Number of jobs for worker to run before exiting(100)',
			's'          => 'run script to do the job',
			'j'          => 'convert the workload to json'
		];
	}

	protected function argDesc() {
		return '<function> <start|stop|restart|help|status>';
	}

	protected function argValid($options) {
		$this->func = $this->opt(-2);

		if (isset($options['s'])) {
			if (!preg_match('#^[a-z][a-z0-9_]+$#i', $this->func)) {
				$this->error($this->func . ' is invalid function name!');

				return false;
			}
			$this->file = APPROOT . 'gearman' . DS . $this->func . '.php';
			if (!is_file($this->file)) {
				$this->error($this->file . ' not found!');

				return false;
			}
			$this->isScript = true;
		} else {
			if (!preg_match('#^[a-z]+(\.[a-z0-9_]+)*$#i', $this->func)) {
				$this->error($this->func . ' is invalid class name!');

				return false;
			}
			$this->file = str_replace('.', '\\', $this->func);
			if (!class_exists($this->file)) {
				$this->error($this->color->str($this->file, 'red') . ' class not found!');

				return false;
			}
			if (!is_subclass_of($this->file, GearmJob::class)) {
				$this->error($this->file . ' is not subclass of ' . GearmJob::class);

				return false;
			}
			$this->cls = new $this->file('1');
			$name      = $this->cls->getFuncName();
			if (!$name || !preg_match('#^[a-z][a-z_\-\d]+$#', $name)) {
				$this->error('Function name "' . $this->color->str($name, 'red') . '" is invalid!');

				return false;
			}
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
		$this->isJson  = isset($options['j']);

		return true;
	}

	protected function setUp(&$options) {
		$this->workerCount = aryget('n', $options, 1);
	}

	protected function worker() {
		if ($this->isScript) {
			$worker = $this->initWorker($this->func, [$this, 'doJob']);
		} else {
			$func   = $this->cls->getFuncName();
			$worker = $this->initWorker($func, [$this, 'doJob']);
		}

		return $worker;
	}

	public function doJob($job) {
		if ($job instanceof \GearmanJob) {
			$wk = $job->workload();
		} else {
			$wk = $job;
		}
		if ($this->isScript) {
			$cmd  = PHP_BINARY;
			$args = escapeshellarg($this->file) . ' ' . escapeshellarg($wk);
			@exec($cmd . ' ' . $args, $output, $rtn);
		} else {
			/**@var GearmJob $cls */
			$cls    = new $this->file($wk);
			$rtn    = $cls->run($this->isJson, false);
			$output = $cls->getOutput();
		}
		if ($job instanceof \GearmanJob) {
			if ($rtn) {
				$job->sendFail();
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

	protected function getOperate() {
		if ($this->arvc < 3) {
			$this->help();
			exit(1);
		}
		if ($this->arvc < 4) {
			$this->arvc   = 4;
			$this->argv[] = 'status';
		}

		return parent::getOperate();
	}

	protected function getPidFilename($cmd) {
		$this->func = $this->opt(-2);

		return $cmd . '-f-' . $this->func;
	}
}