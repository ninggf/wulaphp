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

/**
 * 定时任务
 * @package wulaphp\command
 */
class CronService extends Service {
    public function run() {
        $script   = $this->getOption('script');
        $cron     = $this->getOption('cron');
        $interval = $this->getOption('interval', 10);
        $fixed    = $this->getOption('fixed');//以固定间隔运行
        $env      = (array)$this->getOption('env', []);

        if (!$script) {
            $this->loge('no cron job script specified');

            return false;
        }

        if (!is_file(APPROOT . $script)) {
            $this->loge($script . ' not found');

            return false;
        }

        while (!$this->shutdown) {
            $s = time();
            if ($cron) {
                if (\CrontabHelper::check($s, $cron)) {
                    $this->cron($script, $env);
                }
                sleep(1);
            } else {
                $this->cron($script, $env);
                $e    = time();
                $intv = $interval;
                if ($fixed) {
                    $intv = $interval - ($e - $s);
                    $s    = time();
                }
                $i = $intv;
                while ($i > 0 && !$this->shutdown) {
                    usleep(200);
                    $e = time();
                    $i = $intv - ($e - $s);
                }
            }
        }

        return true;
    }

    /**
     * 运行.
     *
     * @param string     $script
     * @param array|null $env
     *
     * @return int
     */
    private function cron($script, array $env) {
        try {
            if ($script) {
                if (is_file(APPROOT . $script)) {
                    try {
                        $this->logd('start to run cron job: ' . $script);
                        $cmd            = escapeshellcmd(PHP_BINARY);
                        $arg            = escapeshellarg($script);
                        $descriptorspec = [
                            0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
                            1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
                            2 => ["pipe", "w"] // 标准错误，写入到一个文件
                        ];
                        $process        = @proc_open($cmd . ' ' . $arg, $descriptorspec, $pipes, APPROOT, $env);
                        if ($process && is_resource($process)) {
                            $rtn = 0;
                            $pid = 0;
                            while (true) {
                                if ($this->shutdown) {
                                    if (isset($env['loop'])) {
                                        @fwrite($pipes[0], "@shutdown@");
                                    } else {
                                        @proc_terminate($process, SIGINT);
                                    }
                                }
                                $info = proc_get_status($process);
                                if (!$info) break;
                                $pid = $info['pid'];
                                if (!$info['running']) {
                                    $rtn = $info['exitcode'];
                                    break;
                                } else {
                                    sleep(1);
                                }
                            }

                            foreach ($pipes as $p) {
                                @fclose($p);
                            }
                            @proc_close($process);
                            $this->logd($script . ', pid: ' . $pid . ' exits with code: ' . $rtn);

                            return $rtn;
                        } else {
                            $this->loge($cmd . ' ' . $arg . ' cannot run!');
                        }

                    } catch (\Exception $e) {
                        $this->loge($e->getMessage());
                    }
                } else {
                    $this->loge($script . ' not found');
                }
            } else {
                $this->loge('no cron job script specified');
            }
        } catch (\Exception $e) {
            $this->loge($e->getMessage());
        }

        return 1;
    }
}