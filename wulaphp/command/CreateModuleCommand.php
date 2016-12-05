<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class CreateModuleCommand extends ArtisanCommand {
	public function cmd() {
		return 'create-module';
	}

	public function desc() {
		return 'create module structure for your';
	}

	protected function execute($options) {
		return 0;
	}

	protected function getOpts() {
		return ['n::namespace' => 'the namespace of the module'];
	}
}