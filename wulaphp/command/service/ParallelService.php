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
 * Class ParallelService
 * @package wulaphp\command\service
 * @property-read int    $sleep
 * @property-read string $script
 */
class ParallelService extends Service {
    public function run() {
        $script = $this->getOption('script');
        $sleep1 = $this->getOption('sleep', 1);
        $env    = (array)$this->getOption('env', []);
        while (!$this->shutdown) {
            if ($script) {
                if (is_file(APPROOT . $script)) {
                    try {
                        $sleep = $sleep1;
                        $this->logd('start to run ' . $script);
                        $cmd            = escapeshellcmd(PHP_BINARY);
                        $arg            = escapeshellarg($script);
                        $descriptorspec = [
                            0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
                            1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
                            2 => ["pipe", "w"] // 标准错误，子进程向此管道中写入数据
                        ];
                        $process        = @proc_open($cmd . ' ' . $arg, $descriptorspec, $pipes, APPROOT, $env);
                        $output         = '';
                        $error          = '';
                        if ($process && is_resource($process)) {
                            $rtn = 0;
                            $pid = 0;
                            @stream_set_blocking($pipes[1], 0);
                            @stream_set_blocking($pipes[2], 0);
                            while (true) {
                                if ($this->shutdown) {
                                    if (isset($env['loop'])) {
                                        @fwrite($pipes[0], "@shutdown@");
                                    } else {
                                        @proc_terminate($process, SIGINT);
                                    }
                                }
                                $info = @proc_get_status($process);
                                if (!$info) break;
                                $pid = $info['pid'];
                                if (!$info['running']) {
                                    $rtn    = $info['exitcode'];
                                    $output = @fgets($pipes[1], 1024);
                                    $error  = @fgets($pipes[2], 1024);
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
                            if ($rtn == 2) {
                                $sleep = 0;
                            } else if ($rtn != 0) {
                                $this->loge($cmd . ' ' . $arg . ' exit abnormally.' . "[output] {$output}, [error] {$error}");
                                //return false; #允许重试
                            }
                        } else {
                            $this->loge($cmd . ' ' . $arg . ' cannot run!');

                            return false;
                        }
                        // sleep
                        while ($sleep > 0 && !$this->shutdown) {
                            sleep(1);
                            $sleep -= 1;
                        }
                    } catch (\Exception $e) {
                        $this->loge($e->getMessage());

                        return false;
                    }
                } else {
                    $this->loge($script . ' not found');

                    return false;
                }
            } else {
                $this->loge('no script specified');

                return false;
            }
        }//end while
        return true;
    }
}