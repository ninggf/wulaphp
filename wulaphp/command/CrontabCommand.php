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

namespace wulaphp\command;

use wulaphp\artisan\ArtisanMonitoredTask;

class CrontabCommand extends ArtisanMonitoredTask {
	private $clz = '';

	public function cmd() {
		return 'cron';
	}

	public function desc() {
		return 'run a crontab job.';
	}

	protected function getOpts() {
		return ['i::interval' => 'the interval in seconds, default is 1 second.', 'f' => 'run in fixed interval.'];
	}

	protected function argDesc() {
		return '<crontab_job_class>';
	}

	protected function argValid($options) {
		$this->clz = $this->opt();
		if (!$this->clz) {
			$this->error('Please give me a crontab job.');

			return false;
		}
		if (!is_subclass_of($this->clz, '\wulaphp\util\ICrontabJob')) {
			$this->error($this->color->str($this->clz, 'red') . ' is not a valid crontab job.');

			return false;
		}

		return true;
	}

	protected function setUp(&$options) {
		$this->workerCount = 1;
	}

	protected function execute($options) {
		$interval = isset($options['i']) ? abs(floatval($options['i']) * 1000000) : 1000000;
		$interval = $interval ? $interval : 1000000;
		$rtn      = 0;
		/**@var \wulaphp\util\ICrontabJob $clz */
		$clz   = new $this->clz();
		$fixed = isset($options['f']);//是否是以固定间隔执行.
		gc_enable();
		while (!$this->shutdown) {
			$s   = microtime(true);
			$rtn = $clz->run();
			gc_collect_cycles();
			$e    = microtime(true);
			$intv = $interval;
			if ($fixed) {
				$intv = $interval - ($e - $s) * 1000000;
			}
			while ($intv > 0) {
				usleep(5000);
				$intv -= 5000;
			}
		}

		return $rtn;
	}
}