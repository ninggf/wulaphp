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

use wulaphp\artisan\ArtisanDaemonTask;

class CrontabCommand extends ArtisanDaemonTask {
	private $clz = '';

	public function cmd() {
		return 'cron';
	}

	public function desc() {
		return 'run a crontab job.';
	}

	protected function getOpts() {
		return ['i::interval' => 'the interval in seconds', 'f' => 'run in fixed interval.'];
	}

	protected function argDesc() {
		return '<crontab_job_class>';
	}

	protected function argValid($options) {
		$this->clz = $this->opt();
		if (!$this->clz) {
			$this->error('please give a crontab job.');

			return false;
		}
		if (!is_subclass_of($this->clz, '\wulaphp\util\ICrontabJob')) {
			$this->error($this->color->str($this->clz, 'red') . ' is not a valid crontab job.');

			return false;
		}

		return true;
	}

	protected function execute($options) {
		$interval = isset($options['i']) ? abs(floatval($options['i']) * 1000000) : 1000000;
		$interval = $interval ? $interval : 1000000;
		$cmd      = PHP_BINARY . ' "' . APPROOT . 'crontab' . DS . 'cron.php" ' . "'$this->clz'";
		$rtn      = 0;
		$fixed    = isset($options['f']);//是否是以固定间隔执行.
		while (!$this->shutdown) {
			$s = microtime(true);
			@system($cmd, $rtn);
			$e = microtime(true);
			if ($rtn !== 0) {
				break;
			} else {
				$intv = $interval;
				if ($fixed) {
					$intv = $interval - ($e - $s) * 1000000;
				}
				while ($intv > 0) {
					usleep(5000);
					$intv -= 5000;
				}
			}
		}

		return $rtn;
	}
}