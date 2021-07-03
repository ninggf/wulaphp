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
function_exists('pcntl_async_signals') && pcntl_async_signals(true);

/**
 * 后台死循环脚本，可以通过输入"@shutdown@"停止。
 *
 * @package wulaphp\command
 */
abstract class LoopScript {
    const DONE = 1;// 退出，然后睡指定时间后再拉起执行
    const NEXT = 2;// 退出,然后立即拉起执行
    const GOON = 3;// 继续运行run方法
    protected $error   = '';
    private   $running = true;
    private   $command = '';

    /**
     * 启动脚本
     */
    public function start() {
        if (function_exists('stream_set_blocking') && !@stream_set_blocking(STDIN, 0)) {
            echo 'cannot set STDIN to noblock mode';
            exit(1); // 退出，不会被拉起
        }
        if ($this->setUp()) {
            if (function_exists('pcntl_signal')) {
                $signals = [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGTSTP, SIGTTOU];
                $onSig   = \Closure::fromCallable([$this, 'onSignal']);
                foreach (array_unique($signals) as $signal) {
                    @pcntl_signal($signal, $onSig);
                }
            }
            while ($this->running) {
                try {
                    $rst = $this->run();
                    if ($rst === self::GOON) {
                        usleep(100);// 还是要睡一会的,睡醒接着执行run方法，主要是为了优雅的关掉自己
                    } else if ($rst === self::NEXT) {
                        exit(2);// 会立即退出,然后被重新拉起
                    } else {
                        exit(0);// 监控进行会sleep interval,然后被重新执行这个脚本
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    exit(1); // 退出，不会被拉起
                }

                $line = @fgets(STDIN);
                if ($line) {
                    $this->command .= trim($line);
                }
                if ($this->command === '@shutdown@') {
                    $this->running = false;
                }
            }

            exit(0);
        } else {
            if ($this->error) {
                echo $this->error;
            } else {
                echo 'setUp fail';
            }
            exit(1); // 退出，不会被拉起
        }
    }

    /**
     * 设置脚本运行环境。
     *
     * @return bool 环境设置成功返回true,反之返回false.
     */
    protected function setUp() {
        return true;
    }

    /**
     * 获取环境变量.
     *
     * @param string       $name
     * @param string|mixed $default
     *
     * @return mixed
     */
    protected function env(string $name, $default = '') {
        return aryget($name, $_SERVER, $default);
    }

    /**
     * @return bool 是否运行.
     */
    protected final function isRunning(): bool {
        if ($this->running) {
            $line = @fgets(STDIN);
            if ($line) {
                $this->command .= trim($line);
            }
            if ($this->command === '@shutdown@') {
                $this->running = false;
            }
        }

        return $this->running;
    }

    /**
     * 运行脚本任务。
     * @return int
     */
    protected abstract function run(): ?int;

    private function onSignal($sig) {
        $this->running = false;
    }
}