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
	private $workload = false;
	private $out      = [];

	public function __construct($workload = null) {
		global $argv;
		if ($workload) {
			$this->workload = $workload;
		} else if (isset($argv[1])) {
			$this->workload = $argv[1];
		}
	}

	/**
	 * 处理任务.
	 *
	 * @param bool $workloadIsJson
	 * @param bool $output output是否直接echo.
	 *
	 * @return int 0 for success
	 */
	public final function run($workloadIsJson = true, $output = true) {
		$rst = false;
		if ($output) {
			$this->out = null;
		}
		try {
			if ($workloadIsJson && $this->workload) {
				$workload = json_decode($this->workload, true);
			} else if ($this->workload) {
				$workload = $this->workload;
			} else {
				$workload = false;
			}
			if ($workload) {
				$rst = $this->doJob($workload);
			}
		} catch (\Exception $e) {
			log_error($e->getMessage(), 'gearmjob');
			$this->output($e->getMessage());
		}
		if ($rst === false) {
			return 1;
		}

		return 0;
	}

	/**
	 * 输出数据
	 *
	 * @param string $data
	 */
	protected function output($data) {
		if ($this->out === null) {
			echo $data, "\n";
		} else {
			$this->out[] = $data;
		}
	}

	public function getOutput() {
		return $this->out;
	}

	/**
	 * 函数名
	 * @return string
	 */
	public abstract function getFuncName();

	/**
	 * @param array|string $workload
	 */
	protected abstract function doJob($workload);
}