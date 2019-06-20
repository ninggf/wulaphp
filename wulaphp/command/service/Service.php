<?php
declare(ticks=1);
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command\service;

use wulaphp\artisan\Colors;

abstract class Service {
    protected $pid;
    protected $name;
    protected $config   = null;
    protected $shutdown = false;
    protected $color    = null;
    protected $rSignal;
    protected $verbose  = 3;//0:off;1:info;2:error;3:warn;4:debug
    protected $logFile;

    /**
     * Service constructor.
     *
     * @param string $name
     * @param array  $config 配置
     */
    public function __construct($name, array $config) {
        $this->pid     = '[' . @posix_getpid() . ']';
        $this->name    = $name;
        $this->config  = $config;
        $this->color   = new Colors();
        $this->logFile = LOGS_PATH . 'service.' . $this->name . '.log';
    }

    // 监听信号
    public final function initSignal() {
        $signals = [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU];
        foreach (array_unique($signals) as $signal) {
            @pcntl_signal($signal, [$this, 'signal']);
        }
    }

    //处理信号事件
    public function signal($signal) {
        if (!$this->shutdown) {
            $this->logi('signal recived: ' . $signal);
            $this->shutdown = true;
            $this->rSignal  = $signal;
        }
    }

    /**
     * 读取配置.
     *
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    protected function getOption($name, $default = '') {
        if ($this->config && array_key_exists($name, $this->config)) {
            return $this->config[ $name ];
        }

        return $default;
    }

    /**
     * 输出
     *
     * @param string $message
     * @param bool   $rtn
     */
    protected final function output($message, $rtn = true) {
        echo $message, $rtn ? "\n" : '';

        flush();
    }

    protected final function logd($message = '') {
        if ($this->verbose > 3) {
            $msg = '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [DEBUG] ' . $message;
            @file_put_contents($this->logFile, $msg . "\n", FILE_APPEND);
        }
    }

    protected final function logw($message = '') {
        if ($this->verbose > 2) {
            $msg = '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [WARN] ' . $message;
            @file_put_contents($this->logFile, $msg . "\n", FILE_APPEND);
        }
    }

    protected final function loge($message = '') {
        if ($this->verbose > 1) {
            $msg = '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [ERROR] ' . $message;
            @file_put_contents($this->logFile, $msg . "\n", FILE_APPEND);
        }
    }

    protected final function logi($message = '') {
        if ($this->verbose > 0) {
            $msg = '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [INFO] ' . $message;
            @file_put_contents($this->logFile, $msg . "\n", FILE_APPEND);
        }
    }

    public function setVerbose($verbose) {
        if (is_numeric($verbose)) {
            $this->verbose = $verbose;
        } else {
            $this->verbose = strlen($verbose) - strlen(str_replace('v', '', $verbose));
        }
    }

    /**
     * 运行
     * @return mixed
     */
    public abstract function run();
}