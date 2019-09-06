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
use wulaphp\util\DaemonCrontab;
use wulaphp\util\ICrontabJob;

pcntl_async_signals(true);

/**
 * Class CrontabCommand
 * @package wulaphp\command
 * @deprecated
 */
class CrontabCommand extends ArtisanMonitoredTask {
    private $clz = '';

    public function cmd() {
        return 'cron';
    }

    public function desc() {
        return 'run a crontab job in background';
    }

    protected function getOpts() {
        return [
            'i::interval' => 'the interval in seconds, default is 1 second.',
            's::second'   => 'start at second(0-59)',
            'f'           => 'run in fixed interval.'
        ];
    }

    protected function argDesc() {
        return '<job> [start|stop|status|help]';
    }

    protected function argValid($options) {
        $this->clz = $this->opt(0);
        if (is_subclass_of($this->clz, '\wulaphp\util\ICrontabJob')) {
            $rst = true;
        } else {
            $rst = is_file(APPROOT . $this->clz);
        }
        if (!$rst) {
            $this->error('invalid crontab class or script');

            return false;
        }
        if (isset($options['i']) && !preg_match('/^[1-9]\d*$/', $options['i'])) {
            $this->error('arg i must digit');

            return false;
        }
        if (isset($options['s']) && !preg_match('/^(0|[1-5]\d?)$/', $options['s'])) {
            $this->error('arg s must be 0-59');

            return false;
        }

        return true;
    }

    protected function setUp(&$options) {
        $this->workerCount = 1;
    }

    protected function execute($options) {
        $interval = isset($options['i']) ? intval($options['i']) : 1;
        $interval = $interval ? $interval : 1;
        $second   = isset($options['s']) ? intval($options['s']) : null;
        //睡到具体秒再执行
        if (!is_null($second)) {
            $cs = ltrim(date('s'), '0');
            if ($cs != $second) {//秒不同
                if ($cs > $second) {
                    $sleep = $second + 60 - $cs;
                } else {
                    $sleep = $second - $cs;
                }
                sleep($sleep);
            }
        }
        $rtn = 0;
        /**@var \wulaphp\util\ICrontabJob $clz */
        $clz = null;
        if (is_subclass_of($this->clz, '\wulaphp\util\ICrontabJob')) {
            $clz = new $this->clz();
        } else {
            $clz = new ScriptCrontab($this->clz);
        }

        if ($clz instanceof DaemonCrontab) {
            $clz->fork = false;//不能在fork了.
        }

        $fixed = isset($options['f']);//是否是以固定间隔执行.
        gc_enable();
        $run = true;
        while (!$this->shutdown) {
            $s = time();
            if ($run) {
                try {
                    $rtn = $clz->run();
                    if ($rtn && is_string($rtn)) {
                        $this->logi($rtn);
                    }
                } catch (\Exception $e) {
                    $run = false;
                    $rtn = 1;
                    $this->loge($e->getMessage());
                    @posix_kill(@posix_getppid(), SIGTERM);
                    @pcntl_signal_dispatch();
                }
                gc_collect_cycles();
            }
            $e    = time();
            $intv = $interval;
            if ($fixed) {
                $intv = $interval - ($e - $s);
                $s    = time();
            }
            $i = $intv;
            while ($i > 0) {
                sleep(1);
                $e = time();
                $i = $intv - ($e - $s);
            }
        }

        return $rtn;
    }

    protected function loop($options) {
        // NOTHING TO DO.
    }

    protected function paramValid($options) {
        $lz = $this->opt(0);

        if (!$lz) {
            return true;
        }
        $this->defaultOp = 'status';

        return true;
    }

    protected function getPidFilename($cmd) {
        $this->clz = $this->opt(1);

        return $cmd . '-' . md5($this->clz);
    }
}

class ScriptCrontab implements ICrontabJob {
    private $file;
    public  $canRun;

    public function __construct($script) {
        $this->file   = APPROOT . $script;
        $this->canRun = is_file($this->file);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function run() {
        if ($this->canRun) {
            //执行命令
            $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($this->file);
            $rst = @exec($cmd, $output, $rtn);

            return $rtn === 0 ? 0 : ($rst ? $rst : 0);
        } else {
            throw_exception($this->file . ' not found!');
        }

        return 1;
    }
}