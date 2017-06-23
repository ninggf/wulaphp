<?php

namespace wulaphp\artisan;
/**
 * 命令.
 *
 * @author  leo <windywany@gmail.com>
 * @package wulaphp\artisan
 * @since   1.0
 */
abstract class ArtisanCommand {
	protected $pid = '';
	protected $color;
	protected $argv;
	protected $arvc;

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

	protected final function getOptions() {
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
			$l                     = 10 + count($opss);
			$op[ '--' . $opss[0] ] = $l;
		}
		$options = [];
		foreach ($op as $o => $r) {
			$key = trim($o, '-');
			for ($i = 2; $i < $argc; $i++) {
				if (strpos($argv[ $i ], $o) === 0) {
					$ov         = $argv[ $i ];
					$argv[ $i ] = null;
					if ($r == 1 || $r == 11) {
						$options[ $key ] = true;
						break;
					}
					$v = str_replace($o, '', $ov);
					if ($v) {
						if ($r < 10) {
							$options[ $key ] = $v;
							break;
						} else if ($r == 11) {
							$this->help('unkown option: ' . $this->color->str(trim($ov, '-'), 'red'));
						}
					}
					for ($j = $i + 1; $j < $argc; $j++) {
						$v = $argv[ $j ];
						if ($v == '=') {
							$argv[ $j ] = null;
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

			if (($r == 2 || $r == 12) && !isset($options[ $key ])) {
				$this->help('Missing option: ' . $this->color->str($o, 'red'));
			}
		}
		$this->argv[0] = $argv[0];
		$this->argv[1] = $argv[1];
		for ($i = 2; $i < $argc; $i++) {
			if ($argv[ $i ] && preg_match('#^(-([^-]*).*|--(.*))$#', $argv[ $i ], $ms)) {
				if ($ms[2]) {
					$this->help('unkown option: ' . $this->color->str($ms[2], 'red'));
				} else {
					$argv[ $i ] = null;
				}
			}
			if (!is_null($argv[ $i ])) {
				$this->argv[] = $argv[ $i ];
			}
		}
		$this->arvc = count($this->argv);

		return $options;
	}

	protected final function opt($index = -1, $default = '') {
		$argv = $this->argv;
		$argc = $this->arvc;
		if ($index < 0) {
			$index = $argc + $index;
		}
		if ($index < 2) {
			return $default;
		}
		if ($argc > 2 && isset($argv[ $index ])) {
			return $argv[ $index ];
		}

		return $default;
	}

	protected final function log($message = '', $nl = true) {
		$msg = ($nl ? $this->pid : '') . $message . ($nl ? "\n" : '');
		echo $msg;
		flush();
	}

	protected final function error($message) {
		$color = $this->color;
		$msg   = $this->pid . $color->str("ERROR:\n", 'red') . $message . "\n";
		echo $msg;

		flush();
	}

	protected final function success($message) {
		$color = $this->color;
		$msg   = $this->pid . $color->str("SUCCESS:\n", 'green') . $message . "\n";
		echo $msg;
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