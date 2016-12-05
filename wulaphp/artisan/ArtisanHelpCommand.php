<?php
namespace wulaphp\artisan;

class ArtisanHelpCommand extends ArtisanCommand {
	public function help($message = '') {
		global $commands;
		if ($message) {
			echo "ERROR:\n";
			echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
		} else {
			echo "artisan manager script from wula.\n\n";
			echo "USAGE:\n";
		}
		echo "  #php artisan <command> [options]\n\n";
		echo "  command list:\n";

		foreach ($commands as $name => $cmd) {
			echo wordwrap("     " . str_pad($name, 20, ' ', STR_PAD_RIGHT) . $cmd->desc(), 72, "\n  ") . "\n";
		}
		echo "\n  #php artisan help <command> to list command options\n";
		exit(1);
	}

	protected function execute($options) {
		global $argv, $commands;

		if (isset($argv[2]) && isset($commands[ $argv[2] ])) {
			$commands[ $argv[2] ]->help();
		} else {
			$this->help();
		}
	}

	public function desc() {
		return 'show this text or show a command help text';
	}

	public function cmd() {
		return 'help';
	}
}