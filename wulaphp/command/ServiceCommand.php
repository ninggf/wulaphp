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

class ServiceCommand extends ArtisanCommand {
	public function cmd() {
		return 'service';
	}

	public function desc() {
		return 'service run in background';
	}

	protected function execute($options) {
		$this->success('haha');
	}
}