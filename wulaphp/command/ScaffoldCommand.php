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

class ScaffoldCommand extends ArtisanCommand {
	public function cmd() {
		return 'scaffold';
	}

	public function desc() {
		return 'scaffold of wulaphp';
	}

	protected function execute($options) {

	}
}