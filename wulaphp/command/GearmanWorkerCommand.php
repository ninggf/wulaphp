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
use wulaphp\artisan\GmTask;
use wulaphp\artisan\GmWorker;

class GearmanWorkerCommand extends ArtisanMonitoredTask {
    use GmTask;
    private $func;
    private $file;
    private $isScript = false;
    private $isJson   = false;
    /**
     * @var GmWorker
     */
    private $cls;

    public function cmd() {
        return 'gearman';
    }

    public function desc() {
        return 'do gearman jobs in background';
    }

    protected function getOpts() {
        return [
            'H::hosts'   => 'gearman server hosts',
            'p::port'    => 'gearman server port',
            't::timeout' => 'timeout in seconds',
            'n::number'  => 'number of worker to do job(1)',
            'c::count'   => 'number of jobs for worker to run before exiting(100)',
            'f:function' => 'function name to use for jobs',
            'j'          => 'convert the workload to json'
        ];
    }

    protected function argDesc() {
        return '<script> <start|stop|restart|status|help>';
    }

    protected function argValid($options) {
        $this->func = $this->opt(0);
        if ($this->func) {
            $this->file = APPROOT . $this->func;
            if (!is_file($this->file)) {
                $this->error($this->file . ' not found!');

                return false;
            }
            $this->func = aryget('f', $options);
            if (!$this->func) {
                $this->error('Missing "-f" option');

                return false;
            }
            if (!preg_match('#^[a-z][a-z0-9_]+$#i', $this->func)) {
                $this->error($this->func . ' is invalid function name!');

                return false;
            }
            $this->isScript = true;
        } else {
            $this->error('no script to do the job!');

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
        $this->host    = aryget('H', $options, 'localhost');
        $this->port    = aryget('p', $options, 4730);
        $this->timeout = aryget('t', $options, 5);
        $this->count   = aryget('c', $options, 1000);
        $this->isJson  = isset($options['j']);

        return true;
    }

    protected function paramValid($options) {
        $this->func = aryget('f', $options);
        $func       = $this->opt(0);
        if (!$func) {
            if ($this->func) {
                $this->defaultOp = 'status';
            }

            return true;
        }
        $this->defaultOp = 'status';

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
            /**@var GmWorker $cls */
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

    protected function getPidFilename($cmd) {
        return $cmd . '-f-' . $this->func;
    }
}