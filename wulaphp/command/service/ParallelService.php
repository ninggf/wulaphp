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
 * Class ParallelService
 * @package wulaphp\command\service
 * @property-read int    $sleep
 * @property-read string $script
 * @internal
 */
class ParallelService extends Service {
    public function run() {
        $script = $this->getOption('script');
        $sleep1 = max(1, intval($this->getOption('sleep', 1)));
        $env    = (array)$this->getOption('env', []);
        if (!$script) {
            $this->loge('no script specified');

            return false;
        }
        if (!is_file(APPROOT . $script)) {
            $this->loge($script . ' not found');

            return false;
        }
        $this->logd('start to run ' . $script);
        $cmd            = escapeshellcmd(PHP_BINARY);
        $arg            = escapeshellarg($script);
        $proc           = $cmd . ' ' . $arg;
        $descriptorspec = [
            0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
            1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
            2 => ["pipe", "w"] // 标准错误，子进程向此管道中写入数据
        ];
        while (!$this->shutdown) {
            try {
                $sleep   = $sleep1;
                $process = @proc_open($proc, $descriptorspec, $pipes, APPROOT, $env);
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
                                @fwrite($pipes[0], "@shutdown@");
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

                    if ($rtn == 2) {
                        $this->logi($script . ', pid: ' . $pid . ' exits with code: 2, sleep: 0' . "[output] {$output}");
                        $sleep = 0;
                    } else if ($rtn != 0) {
                        $this->loge($cmd . ' ' . $arg . ' exit abnormally.' . " [output] {$output}, [error] {$error}");
                    } else {
                        $this->logd($script . ', pid: ' . $pid . ' exits with code: 0, sleep: ' . $sleep);
                    }
                    unset($process, $output, $error, $pipes);
                    // sleep
                    while ($sleep > 0 && !$this->shutdown) {
                        sleep(1);
                        $sleep -= 1;
                    }
                } else {
                    $this->loge($proc . ' cannot run!');

                    return false;
                }
            } catch (\Exception $e) {
                $this->loge($e->getMessage());

                return false;
            }
        }//end while
        $this->logi('shutdown');

        return true;
    }
}