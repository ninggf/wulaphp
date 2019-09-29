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

/**
 * Class RunCommand
 * @package wulaphp\command
 * @deprecated
 * @internal
 */
class RunCommand extends ArtisanMonitoredTask {
    private $script;
    private $logfile;

    public function cmd() {
        return 'run';
    }

    public function desc() {
        return 'parallel run a script in background';
    }

    public function argDesc() {
        return '<script> [start|stop|status|help]';
    }

    protected function getOpts() {
        return [
            'n::number'  => 'Number of worker to run the script(4)',
            'l::logfile' => 'log file'
        ];
    }

    protected function paramValid($options) {
        $s = $this->opt(0);

        if (!$s) {
            return true;
        }
        if (!preg_match('/\.php$/', $s)) {
            $this->error($s . ' is an invalid script name!');

            return false;
        }
        $this->script = APPROOT . $s;
        if (!is_file($this->script)) {
            $this->error($this->script . ' not found!');

            return false;
        }
        $this->defaultOp = 'status';

        return true;
    }

    protected function argValid($options) {
        $s = $this->opt(0);

        if (!$s) {
            $this->error('no script to run!');

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