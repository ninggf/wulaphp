<?php
namespace wulaphp\artisan;

class ArtisanHelpCommand extends ArtisanCommand {
	public function help($message = '') {
		$color = new Colors();
		global $commands;
		if ($message) {
			echo $color->str("ERROR:\n", 'red');
			echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
		} else {
			echo "\nartisan manager script from wula.\n\n";
			echo $color->str("USAGE:\n", 'green');
		}
		echo "  #php artisan <command> [options] [args]\n\n";
		echo "  command list:\n";
		/** @var ArtisanCommand $cmd 命令实例 */
		foreach ($commands as $name => $cmd) {
			echo wordwrap("     " . $color->str(str_pad($name, 20, ' ', STR_PAD_RIGHT), 'green') . $cmd->desc(), 100, "\n" . str_pad('', 25, ' ', STR_PAD_RIGHT)) . "\n";
		}
		echo "\n  #php artisan help <command> to list command options and args\n\n";
		if ($message) {
			exit(1);
		} else {
			exit(0);
		}
	}

	protected function execute($options) {
		global $argv, $commands;
		/** @var ArtisanCommand $cmd 命令实例 */
		if (isset($argv[2]) && isset($commands[ $argv[2] ])) {
			$cmd = $commands[ $argv[2] ];
			$cmd->help();
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