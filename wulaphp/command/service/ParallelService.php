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
        $sleep1 = $this->getOption('sleep', 10);
        $env    = (array)$this->getOption('env', []);
        while (!$this->shutdown) {
            $sleep = $sleep1;
            if ($script) {
                if (is_file(APPROOT . $script)) {
                    try {
                        $this->logd('start to run ' . $script);
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
                                    @proc_terminate($process, SIGINT);
                                    break;
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
                            if ($rtn == 2) {
                                $sleep = 0;
                            }
                        } else {
                            $this->loge($cmd . ' ' . $arg . ' cannot run!');

                            return false;
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
            while ($sleep > 0) {
                sleep(1);
                $sleep -= 1;
            }
        }

        return true;
    }
}