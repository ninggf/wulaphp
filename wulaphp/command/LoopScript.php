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
/**
 * 后台死循环脚本，可以通过输入"@shutdown@"停止。
 *
 * @package wulaphp\command
 */
abstract class LoopScript {
    const DONE = 1;//会sleep一会然后退出,然后被重新拉起
    const NEXT = 2;//会立即退出,然后被重新拉起
    const GOON = 3;//继续运行run方法
    protected $error   = '';
    private   $running = true;
    private   $command = '';

    /**
     * 启动脚本
     */
    public function start() {
        if (!@stream_set_blocking(STDIN, 0)) {
            echo 'cannot set STDIN to noblock mode';
            exit(1);
        }
        if ($this->setUp()) {
            do {
                try {
                    $rst = $this->run();
                    if ($rst === self::DONE) {
                        exit(0);// 会sleep一会然后退出,然后被重新拉起
                    } else if ($rst === self::NEXT) {
                        exit(2);// 会立即退出,然后被重新拉起
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    exit(1);
                }
                $line = @fgets(STDIN);
                if ($line) {
                    $this->command .= trim($line);
                }
                if ($this->command === '@shutdown@') {
                    $this->running = false;
                }
            } while ($this->running);
            exit(0);
        } else {
            if ($this->error) {
                echo $this->error;
            } else {
                echo 'setUp fail';
            }
            exit(1);
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
    protected function env($name, $default = '') {
        return aryget($name, $_SERVER, $default);
    }

    protected final function isRunning() {
        $line = @fgets(STDIN);
        if ($line) {
            $this->command .= trim($line);
        }
        if ($this->command === '@shutdown@') {
            $this->running = false;
        }

        return $this->running;
    }

    /**
     * 运行脚本任务。
     * @return bool 成功返回true,反之返回false.
     */
    protected abstract function run();
}