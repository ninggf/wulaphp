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
		$this->log("\tchmod ( 'tmp', 0777 )");
		chmod('tmp', 0777);
		$this->log("\tchmod ( 'logs', 0777 )");
		chmod('logs', 0777);
		$this->log("\tcopy .env.example to .env");
		$content = file_get_contents('.env.example');
		file_put_contents('.env', $content);
		$appid = rand(1, 10000);
		$this->log("\tgenerated appid: " . $appid);
		$content = file_get_contents('bootstrap.php');
		$content = str_replace("'app1'", "'app" . $appid . "'", $content);
		file_put_contents('bootstrap.php', $content);
	}
}