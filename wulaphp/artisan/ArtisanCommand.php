<?php
namespace wulaphp\artisan;

abstract class ArtisanCommand {
	protected $color;

	public function __construct() {
		$this->color = new Colors();
	}

	public function help($message = '') {
		$color = $this->color;
		if ($message) {
			echo $color->str("ERROR:\n", 'red');
			echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
		}
		$opts  = $this->getOpts();
		$lopts = $this->getLongOpts();
		echo wordwrap($this->desc(), 72, "\n  ") . "\n\n";
		echo $color->str("USAGE:\n", 'green');
		echo "  #php artisan " . $this->cmd() . (($opts || $lopts) ? ' [options] ' : ' ') . $color->str($this->argDesc(), 'blue') . "\n\n";

		foreach ($opts as $opt => $msg) {
			$opss = explode(':', $opt);
			$l    = count($opss);
			$arg  = $opss[ $l - 1 ];
			$str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 24, ' ', STR_PAD_RIGHT);
			echo "    " . $color->str('-' . $str, 'green') . wordwrap($msg, 72, str_pad("\n", 28, ' ', STR_PAD_RIGHT)) . "\n";
		}

		foreach ($lopts as $opt => $msg) {
			$opss = explode(':', $opt);
			$l    = count($opss);
			$arg  = $opss[ $l - 1 ];
			$str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 23, ' ', STR_PAD_RIGHT);
			echo "    " . $color->str('--' . $str, 'green') . wordwrap($msg, 72, str_pad("\n", 28, ' ', STR_PAD_RIGHT)) . "\n";
		}
		echo "\n";
		if ($message) {
			exit (1);
		} else {
			exit (0);
		}
	}

	protected function getOpts() {
		return [];
	}

	protected function getLongOpts() {
		return [];
	}

	protected function getOptions() {
		global $argv, $argc;
		$op   = [];
		$opts = $this->getOpts();
		foreach ($opts as $opt => $msg) {
			$opss                 = explode(':', $opt);
			$l                    = count($opss);
			$op[ '-' . $opss[0] ] = $l;
		}
		$opts = $this->getLongOpts();
		foreach ($opts as $opt => $msg) {
			$opss                  = explode(':', $opt);
			$l                     = count($opss);
			$op[ '--' . $opss[0] ] = $l;
		}
		$options = [];
		foreach ($op as $o => $r) {
			$key = trim($o, '-');
			for ($i = 2; $i < $argc; $i++) {
				if ($argv[ $i ] == $o) {
					if ($r == 1) {
						$options[ $key ] = true;
						break;
					}
					for ($j = $i + 1; $j < $argc; $j++) {
						$v = $argv[ $j ];
						if ($v == '=') {
							continue;
						} elseif (strpos('-', $v) === 0) {
							break;
						} else {
							$argv[ $j ]      = null;
							$options[ $key ] = $v;
							break;
						}
					}
				}
			}
			if ($r == 2 && !isset($options[ $key ])) {
				$this->help('Missing option: ' . $this->color->str($o, 'red'));
			}
		}

		return $options;
	}

	protected function opt($index = -1, $default = '') {
		global $argv, $argc;
		if ($index < 0) {
			$index = $argc + $index;
		}
		if ($argc > 2 && isset($argv[ $index ])) {
			return $argv[ $index ];
		}

		return $default;
	}

	protected function log($message = '', $nl = true) {
		echo $message;
		if ($nl) echo "\n";
		flush();
	}

	protected function error($message) {
		$color = $this->color;
		echo $color->str("ERROR:\n", 'red');
		echo $message, "\n";
		flush();
	}

	protected function success($message) {
		$color = $this->color;
		echo $color->str("SUCCESS:\n", 'green');
		echo $message, "\n";
		flush();
	}

	public function run() {
		$options = $this->getOptions();

		return $this->execute($options);
	}

	protected function argDesc() {
		return '';
	}

	public abstract function cmd();

	public abstract function desc();

	protected abstract function execute($options);
}

