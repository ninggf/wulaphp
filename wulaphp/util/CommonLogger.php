<?php

namespace wulaphp\util;

use Psr\Log\LoggerInterface;

/**
 * Class CommonLogger
 *
 * @package wulaphp\util
 * @since   1.1.0
 * @author  Leo Ning <windywany@gmail.com>
 */
class CommonLogger implements LoggerInterface {
	private static $log_name = array(DEBUG_INFO => 'INFO', DEBUG_WARN => 'WARN', DEBUG_DEBUG => 'DEBUG', DEBUG_ERROR => 'ERROR');
	private        $file     = '';

	public function __construct($file = 'wula') {
		$this->file = $file;
	}

	public function emergency($message, array $context = array()) {
		$this->log(DEBUG_ERROR, $message, $context);
	}

	public function alert($message, array $context = array()) {
		$this->log(DEBUG_ERROR, $message, $context);
	}

	public function critical($message, array $context = array()) {
		$this->log(DEBUG_ERROR, $message, $context);
	}

	public function error($message, array $context = array()) {
		$this->log(DEBUG_ERROR, $message, $context);
	}

	public function warning($message, array $context = array()) {
		$this->log(DEBUG_WARN, $message, $context);
	}

	public function notice($message, array $context = array()) {
		$this->log(DEBUG_INFO, $message, $context);
	}

	public function info($message, array $context = array()) {
		$this->log(DEBUG_INFO, $message, $context);
	}

	public function debug($message, array $context = array()) {
		$this->log(DEBUG_DEBUG, $message, $context);
	}

	public function log($level, $message, array $trace_info = array()) {
		$file = $this->file;

		$ln  = isset(self::$log_name [ $level ]) ? self::$log_name [ $level ] : 'WARN';
		$msg = date("Y-m-d H:i:s") . " [$ln] {$message}\n\t{$trace_info[0]['file']} at line {$trace_info[0]['line']}\n";
		if (isset ($trace_info [1]) && $trace_info [1]) {
			$msg .= "\t\t{$trace_info[1]['file']} at line {$trace_info[1]['line']}\n";
			if (isset ($trace_info [2]) && $trace_info [2]) {
				$msg .= "\t\t\t{$trace_info[2]['file']} at line {$trace_info[2]['line']}\n";
			}
			if (isset ($trace_info [3]) && $trace_info [3]) {
				$msg .= "\t\t\t\t{$trace_info[3]['file']} at line {$trace_info[3]['line']}\n";
			}
			if (isset ($trace_info [4]) && $trace_info [4]) {
				$msg .= "\t\t\t\t{$trace_info[4]['file']} at line {$trace_info[4]['line']}\n";
			}
			if (isset ($trace_info [5]) && $trace_info [5]) {
				$msg .= "\t\t\t\t{$trace_info[5]['file']} at line {$trace_info[5]['line']}\n";
			}
		}
		if (isset ($_SERVER ['REQUEST_URI'])) {
			$msg .= "\turi: " . $_SERVER ['REQUEST_URI'] . "\n";
		}
		$dest_file = $file ? $file . '.log' : 'wula.log';
		@error_log($msg, 3, LOGS_PATH . $dest_file);
	}
}