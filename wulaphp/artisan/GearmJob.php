<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\artisan;

abstract class GearmJob {
	protected $workload = false;

	public function __construct() {
		global $argv;
		if (isset($argv[1])) {
			$this->workload = $argv[1];
		}
	}

	/**
	 * 处理任务.
	 *
	 * @param bool $workloadIsJson
	 */
	public final function run($workloadIsJson = true) {
		if ($workloadIsJson && $this->workload) {
			$workload = json_decode($this->workload, true);
		} else if ($this->workload) {
			$workload = $this->workload;
		} else {
			$workload = false;
		}
		$rst = false;
		if ($workload) {
			$rst = $this->doJob($workload);
		}
		if ($rst === false) {
			exit(1);
		}
		exit(0);
	}

	/**
	 * 输出数据
	 *
	 * @param string $data
	 */
	protected function output($data) {
		echo $data, "\n";
	}

	/**
	 * @param array|string $workload
	 */
	protected abstract function doJob($workload);
}