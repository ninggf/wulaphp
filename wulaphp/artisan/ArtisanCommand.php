<?php
namespace wulaphp\artisan;

abstract class ArtisanCommand {
	public function help($message = '') {
		if ($message) {
			echo "ERROR:\n";
			echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
		}
		$opts = $this->getOpts();
		echo wordwrap($this->desc(), 72, "\n  ") . "\n\n";
		echo "USAGE:\n";
		echo "  #php artisan.php " . $this->cmd() . ' [options] ' . "\n";
		foreach ($opts as $opt => $msg) {
			$opss = explode(':', $opt);
			$l    = count($opss);
			$arg  = $opss[ $l - 1 ];
			$str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 24, ' ', STR_PAD_RIGHT);
			echo wordwrap("    -" . $str . $msg, 72, "\n ") . "\n";
		}
		$opts = $this->getLongOpts();
		foreach ($opts as $opt => $msg) {
			$opss = explode(':', $opt);
			$l    = count($opss);
			$arg  = $opss[ $l - 1 ];
			$str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 24, ' ', STR_PAD_RIGHT);
			echo wordwrap("    --" . $str . $msg, 72, "\n ") . "\n";
		}
		exit(1);
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
							$options[ $key ] = $v;
							break;
						}
					}
				}
			}
			if ($r == 2 && !isset($options[ $key ])) {
				$this->help('Miss option:' . $o);
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

	protected function log($message) {
		echo $message, "\n";
		flush();
	}

	public function run() {
		$options = $this->getOptions();

		return $this->execute($options);
	}

	public abstract function cmd();

	public abstract function desc();

	protected abstract function execute($options);
}

