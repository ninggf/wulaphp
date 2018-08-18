<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command\service;

use wulaphp\artisan\GearmanCommand;
use wulaphp\artisan\GearmJob;

/**
 * Class GearmanService
 * @package wulaphp\command\service
 * @property-read string $host     主机，默认localhost
 * @property-read int    $port     端口，默认4730
 * @property-read int    $timeout  超时
 * @property-read int    $count    运行多少个任务后重启
 * @property-read bool   $json     内容是否是json格式
 * @property-read string $job      任务
 * @property-read string $jobClass 任务类
 */
class GearmanService extends Service {
	use GearmanCommand;
	protected $funcName;
	protected $jobClass;
	protected $isJson;
	private   $jobFile;

	public function run() {
		$this->host     = $this->getOption('host', 'localhost');
		$this->port     = $this->getOption('port', '4730');
		$this->timeout  = $this->getOption('timeout', 5);
		$this->count    = $this->getOption('count', 100);
		$this->isJson   = $this->getOption('json', true);
		$this->funcName = $this->getOption('job');

		if (empty($this->funcName)) {
			$this->loge('no job specified!');

			return false;
		}
		$this->jobClass = $this->getOption('jobClass');
		if (empty($this->jobClass)) {
			$this->loge('no jobClass specified!');

			return false;
		}
		if (is_file(APPROOT . $this->jobClass)) {
			$this->jobFile = $this->jobClass;
		} else if (!is_subclass_of($this->jobClass, GearmJob::class)) {
			$this->loge($this->jobClass . ' is not subclass of ' . GearmJob::class);

			return false;
		}

		return $this->execute([]);
	}

	protected function worker() {
		return $this->initWorker($this->funcName, [$this, 'doJob']);
	}

	public function doJob($job) {
		if ($job instanceof \GearmanJob) {
			$wk = $job->workload();
		} else {
			$wk = $job;
		}
		$this->logd('[workload] ' . $wk);
		if ($this->jobFile) {
			$cmd  = escapeshellcmd(PHP_BINARY);
			$args = escapeshellarg($this->jobFile) . ' ' . escapeshellarg($wk);
			chdir(APPROOT);
			@exec($cmd . ' ' . $args, $output, $rtn);
		} else {
			/**@var \wulaphp\artisan\GearmJob $cls */
			$cls    = new $this->jobClass($wk);
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
}