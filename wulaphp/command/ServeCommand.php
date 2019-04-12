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

use wulaphp\artisan\ArtisanCommand;

class ServeCommand extends ArtisanCommand {
    public function cmd() {
        return 'serve';
    }

    public function desc() {
        return 'run build-in server for development';
    }

    public function getOpts() {
        return ['p::port' => 'TCP port to listen on (default: 9090)'];
    }

    protected function execute($options) {
        $port           = intval(aryget('p', $options, 9090));
        $cmd            = escapeshellcmd(PHP_BINARY);
        $wwwroot        = trailingslashit(PUBLIC_DIR . WWWROOT_DIR);
        $arg            = escapeshellarg("-S '127.0.0.1:{$port}' -t " . $wwwroot . ' ' . $wwwroot . 'index.php');
        $descriptorspec = [
            0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
            1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
            2 => ["pipe", "w"] // 标准错误，写入到一个文件
        ];
        echo $cmd, ' ', $arg, "\n";
    }
}