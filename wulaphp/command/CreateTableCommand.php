<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class CreateTableCommand extends ArtisanCommand {
	public function cmd() {
		return 'create-table';
	}

	public function desc() {
		return 'create table class based on the table in database.';
	}

	protected function execute($options) {
		return 0;
	}

	protected function getOpts() {
		return ['m:module' => 'which module the Table class belongs to.', 't:table' => 'the table name'];
	}
}