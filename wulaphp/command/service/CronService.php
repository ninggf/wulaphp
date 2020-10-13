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

/**
 * 定时任务
 * @package wulaphp\command
 * @internal
 */
class CronService extends Service {
    private $proc;
    private $descriptorspec;
    private $nextRunTime = 0;

    public function run() {
        $script   = $this->getOption('script');
        $interval = $this->getOption('interval', $this->getOption('sleep', 10));
        $fixed    = $this->getOption('fixed');//以固定间隔运行
        $crontab  = $this->getOption('crontab');
        $env      = (array)$this->getOption('env', []);

        if (!$script) {
            $this->loge('no cron job script specified');

            return false;
        }

        if (!is_file(APPROOT . $script)) {
            $this->loge($script . ' not found');

            return false;
        }
        $cmd                  = escapeshellcmd(PHP_BINARY);
        $arg                  = escapeshellarg($script);
        $this->proc           = $cmd . ' ' . $arg;
        $this->descriptorspec = [
            0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
            1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
            2 => ["pipe", "w"] // 标准错误，子进程向此管道中写入数据
        ];

        @cli_set_process_title('php -name ' . $this->name . ' -type cron ' . trim($arg, "'"));

        if ($crontab) {
            $crontab1 = preg_split('/\s+/', $crontab);
            while (!$this->shutdown) {
                $ctime = time();
                if ($ctime >= $this->nextRunTime && \CrontabHelper::check($ctime, $crontab)) {
                    if ($crontab1[0] != '*') {
                        $this->nextRunTime = \CrontabHelper::next_runtime($crontab) + 1;
                    } else {
                        $this->nextRunTime = \CrontabHelper::next_runtime($crontab) + 60;
                    }
                    $this->logd($this->nameStr . '开始运行');
                    $rtn = $this->cron($script, $env);
                    $this->logd($this->nameStr . '运行结束,退出状态码: ' . $rtn);
                } else {
                    sleep(1);
                }
            }
        } else {
            while (!$this->shutdown) {
                $this->runInterval($script, $env, $fixed, $interval);
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
    private function cron(string $script, ?array $env = null) {
        try {
            $this->logi('cron : ' . $script);
            $process = @proc_open($this->proc, $this->descriptorspec, $pipes, APPROOT);
            $output  = '';
            $error   = '';
            if ($process && is_resource($process)) {
                $rtn = 0;
                @stream_set_blocking($pipes[1], 0);
                @stream_set_blocking($pipes[2], 0);
                do {
                    $pid = @pcntl_wait($status, WNOHANG);
                    if ($pid == 0) {
                        if ($this->shutdown) {
                            if (isset($env['loop'])) {
                                @fwrite($pipes[0], "@shutdown@");
                            }
                            @proc_terminate($process, SIGINT);
                        }
                        usleep(rand(300, 500));
                    } else if ($pid > 0) {//exit
                        $rtn    = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : 1;
                        $output = @fgets($pipes[1], 1024);
                        $error  = @fgets($pipes[2], 1024);
                    } else {// error
                        $rtn   = 1;
                        $error = pcntl_strerror(pcntl_get_last_error());
                    }
                } while ($pid == 0);

                foreach ($pipes as $p) {
                    @fclose($p);
                }
                @proc_close($process);

                if ($rtn != 0) {
                    $this->loge($script . ', pid: ' . $pid . ' exits with code: ' . $rtn . ", [output] {$output}, [error] {$error}");
                }
                unset($process, $output, $error, $pipes);

                return $rtn;
            } else {
                $this->loge($this->proc . ' cannot run!');
            }
        } catch (\Exception $e) {
            $this->loge($e->getMessage());
        }

        return 1;
    }

    /**
     * @param string $script
     * @param array  $env
     * @param string $fixed
     * @param string $interval
     */
    private function runInterval(string $script, array $env, string $fixed, string $interval): void {
        $s = time();
        $this->cron($script, $env);
        $e = time();
        if ($fixed) {
            $intv = $interval - ($e - $s);
            $s    = time();
        } else {
            $intv = $interval;
        }
        $i = $intv;
        while ($i > 0 && !$this->shutdown) {
            sleep(1);
            $e = time();
            $i = $intv - ($e - $s);
        }
    }
}