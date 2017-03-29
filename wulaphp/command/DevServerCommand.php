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
		passthru($cmd, $rtn);

		return $rtn;
	}
}