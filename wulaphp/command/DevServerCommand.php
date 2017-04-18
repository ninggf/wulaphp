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

class DevServerCommand extends ArtisanCommand {
	public function cmd() {
		return 'serve';
	}

	public function desc() {
		return 'run a built-in web server for development';
	}

	protected function getOpts() {
		return ['l::addr' => 'default is 127.0.0.1', 'p::port' => 'default is 8080'];
	}

	protected function execute($options) {
		$addr = $options['l'] ? $options['l'] : '127.0.0.1';
		$port = $options['p'] ? $options['p'] : '8080';

		$cmd = PHP_BINARY . ' -S ' . $addr . ':' . $port . ' -t ' . PUBLIC_DIR . ' ' . PUBLIC_DIR . '/index.php';

		$descriptorspec = [0 => ['pipe', 'r'],  // 标准输入，子进程从此管道中读取数据
		                   1 => ['pipe', 'w'],  // 标准输出，子进程向此管道中写入数据
		                   2 => ['pipe', 'w'] // 标准错误，写入到一个文件
		];

		$process = proc_open($cmd, $descriptorspec, $pipes, getcwd());

		if ($pipes) {
			if ($process) {
				$date = date('Y-m-d H:i:s');
				echo "Development Server started at $date
Listening on http://$addr:$port 
Press Ctrl-C to quit.\n";
				flush();
				proc_close($process);
			}
		}

		return 0;
	}
}