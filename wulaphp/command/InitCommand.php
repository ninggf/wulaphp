<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class InitCommand extends ArtisanCommand {
	public function cmd() {
		return 'init';
	}

	public function desc() {
		return 'initialize wulaphp application.';
	}

	protected function execute($options) {
		$this->log($this->color->str("initializing ... ", 'green'));
		$this->log("\tchmod ( 'tmp', 0777 )");
		if (!is_dir('tmp')) {
			mkdir('tmp');
		}
		chmod('tmp', 0777);
		if (!is_dir('logs')) {
			mkdir('logs');
		}
		$this->log("\tchmod ( 'logs', 0777 )");
		chmod('logs', 0777);
		$this->log(' [' . $this->color->str('done', 'green') . ']');

		return 0;
	}
}