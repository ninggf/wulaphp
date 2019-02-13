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
    protected $sleep   = 0;
    private   $running = true;

    /**
     * 启动脚本
     */
    public function start() {
        if (!@stream_set_blocking(STDIN, 0)) {
            exit(1);
        }
        if ($this->setUp()) {
            $command = '';
            do {
                try {
                    $rst = $this->run();
                    if ($rst === false) {
                        $this->running = false;
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    exit(1);
                }
                $line = @fgets(STDIN);
                if ($line) {
                    $command .= trim($line);
                }
                if ($command === '@shutdown@') {
                    $this->running = false;
                }
                if ($this->running && $this->sleep) {
                    @sleep($this->sleep);
                }
            } while ($this->running);
            exit(0);
        } else {
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

    /**
     * 运行脚本任务。
     * @return bool 成功返回true,反之返回false.
     */
    protected abstract function run();
}