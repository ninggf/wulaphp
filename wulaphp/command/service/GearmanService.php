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

use wulaphp\artisan\GmWorker;

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
 * @internal
 */
class GearmanService extends Service {
    protected $funcName;
    protected $jobClass;
    protected $isJson;
    private   $jobFile;
    /**@var \wulaphp\artisan\GmWorker */
    private $workerCls;

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
        $this->jobClass = $this->getOption('workerClass', $this->getOption('script'));
        if (empty($this->jobClass)) {
            $this->loge('no worker specified!');

            return false;
        }
        if (is_file(APPROOT . $this->jobClass)) {
            $this->jobFile = $this->jobClass;
        } else if (!is_subclass_of($this->jobClass, GmWorker::class)) {
            $this->loge($this->jobClass . ' is not subclass of ' . GmWorker::class);

            return false;
        } else {
            $this->workerCls = new $this->jobClass();
        }

        return $this->execute([]);
    }

    protected function execute(/** @noinspection PhpUnusedParameterInspection */ $options) {
        $worker = $this->initWorker($this->funcName, [$this, 'doJob']);
        if (!$worker) {
            return false;
        }
        $count = 0;//运行成功多少次后重启

        while (!$this->shutdown && $count < $this->count) {
            try {
                $worker->work();
                $status = $worker->returnCode();
                switch ($status) {
                    case GEARMAN_SUCCESS:
                        $count++;
                        break;
                    case GEARMAN_NO_ACTIVE_FDS:
                    case GEARMAN_IO_WAIT:
                        $i = 10;
                        while ($i > 0 && !$this->shutdown) {
                            sleep(1);
                            $i--;
                        }
                        break;
                    case GEARMAN_NO_JOBS:
                    case GEARMAN_WORKER_WAIT_TIMEOUT:
                    case GEARMAN_TIMEOUT:
                    case GEARMAN_WORKER_TIMEOUT_RETURN:
                    case GEARMAN_WORK_FAIL:
                        sleep(1);
                        break;
                    default:
                        sleep(1);
                }
            } catch (\Exception $e) {
                $this->loge($e->getMessage());
                break;
            }
        }

        $worker->unregisterAll();
        $worker = null;

        return true;
    }

    /**
     * 初始化GearmanWorker.
     *
     * @param string   $func
     * @param callable $cb
     *
     * @return \GearmanWorker|null
     */
    protected function initWorker($func, $cb = null): ?\GearmanWorker {
        try {
            $worker = new \GearmanWorker();
            $worker->setId($func . '@' . posix_getpid());
            $worker->setTimeout($this->timeout ? $this->timeout * 1000 : 5000);
            // Add Gearman Job Servers to Worker
            if (strpos($this->host, ',') > 0) {
                $connected = $worker->addServers($this->host);
            } else {
                $connected = $worker->addServer($this->host, $this->port);
            }
            if ($connected) {
                if (is_array($func)) {
                    foreach ($func as $f) {
                        $worker->addFunction($f[0], $f[1]);
                    }
                } else if (is_callable($cb)) {
                    $worker->addFunction($func, $cb);
                } else {
                    unset($worker);

                    return null;
                }

                return $worker;
            } else {
                $this->loge('Cannot connect to Gearmand Server: ' . $worker->error());
            }
        } catch (\Exception $exception) {
            $this->loge($exception->getMessage());
        }

        return null;
    }

    /**
     * 干活.
     *
     * @param string|\GearmanJob $job
     *
     * @return string
     */
    public function doJob($job) {
        if ($job instanceof \GearmanJob) {
            $wk    = $job->workload();
            $jobId = $job->unique();
        } else {
            $wk    = $job;
            $job   = null;
            $jobId = '0';
        }
        $this->logd('[workload] ' . $jobId . ' => ' . $wk);
        if ($this->jobFile) {
            $cmd  = escapeshellcmd(PHP_BINARY);
            $args = escapeshellarg($this->jobFile) . ' ' . escapeshellarg($wk) . ' ' . escapeshellarg($this->funcName) . ' ' . escapeshellarg($jobId);
            chdir(APPROOT);
            // 优化为CGI调用.
            @exec($cmd . ' ' . $args, $output, $rtn);
        } else {
            $this->workerCls->setWorkload($wk, $job);
            $rtn    = $this->workerCls->run($this->isJson, false);
            $output = $this->workerCls->getOutput();
        }
        if ($job instanceof \GearmanJob) {
            if ($rtn) {
                $job->sendFail();

                return '';
            }
        }

        if ($output) {
            return implode("\n", $output);
        }

        return '';
    }
}