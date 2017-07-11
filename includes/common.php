<?php
/**
 * 取数据.
 *
 * @param string $name
 * @param string $default
 * @param bool   $xss_clean
 *
 * @return mixed
 */
function rqst($name, $default = '', $xss_clean = true) {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}

	return $__rqst->get($name, $default, $xss_clean);
}

/**
 * 一次取多个值.
 *
 * @param array $names 表单中的字段名.
 * @param bool  $xss_clean
 * @param array $map   表单字段与结果字段的映射
 *
 * @return array
 */
function rqsts($names, $xss_clean = true, $map = []) {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}
	$rqts = [];
	foreach ($names as $key => $default) {
		if (is_numeric($key)) {
			$fname   = $default;
			$default = '';
		} else {
			$fname = $key;
		}
		$rname          = isset($map[ $fname ]) ? $map[ $fname ] : $fname;
		$rqts[ $rname ] = $__rqst->get($fname, $default, $xss_clean);
	}

	return $rqts;
}

/**
 * @param string $name
 * @param string $default
 *
 * @return mixed
 */
function arg($name, $default = '') {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}

	return $__rqst->get($name, $default, false);
}

/**
 * 是否有该请求数据.
 *
 * @param string $name
 *
 * @return bool
 */
function rqset($name) {
	return isset ($_REQUEST[ $name ]);
}

/**
 * 取int型参数。
 *
 * @param string $name
 * @param int    $default
 *
 * @return int
 */
function irqst($name, $default = 0) {
	return intval(rqst($name, $default, true));
}

/**
 * 取float型参数.
 *
 * @param string $name
 * @param int    $default
 *
 * @return float
 */
function frqst($name, $default = 0) {
	return floatval(rqst($name, $default, true));
}

/**
 * 记录debug信息.
 *
 * @param string $message
 * @param string $file
 */
function log_debug($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_DEBUG, $file);
}

/**
 * 记录info信息.
 *
 * @param string $message
 * @param string $file
 */
function log_info($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_INFO, $file);
}

/**
 * 记录warn信息.
 *
 * @param string $message
 * @param string $file
 */
function log_warn($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_WARN, $file);
}

/**
 * 记录error信息.
 *
 * @param string $message
 * @param string $file
 */
function log_error($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_ERROR, $file);
}

/**
 * log.
 *
 * @param string $message
 * @param array  $trace_info
 * @param int    $level debug,info,warn,error
 * @param string $file
 *
 * @filter logger\getLogger $logger $level $file
 */
function log_message($message, $trace_info, $level, $file = 'wula') {
	//记录关闭.
	if (DEBUG == DEBUG_OFF) {
		return;
	}
	static $loggers = [];
	if (!isset($loggers[ $level ][ $file ])) {
		//获取日志器.
		$log = apply_filter('logger\getLogger', new \wulaphp\util\CommonLogger($file), $level, $file);
		if ($log instanceof Psr\Log\LoggerInterface) {
			$logger = $log;
		} else {
			$logger = null;
		}
		$loggers[ $level ][ $file ] = $logger;
	}

	if (empty ($trace_info)) {
		return;
	}

	if ($level >= DEBUG && $loggers[ $level ][ $file ]) {
		$loggers[ $level ][ $file ]->log($level, $message, $trace_info);
	}
}

/**
 * 得到session名.
 *
 * @return mixed
 * @filter  get_session_name session_name
 */
function get_session_name() {
	return apply_filter('get_session_name', 'phpsid');
}

/**
 * 生成SQL中不可变字符.
 *
 * @param string $val
 * @param string $alias
 *
 * @return \wulaphp\db\sql\ImmutableValue
 */
function imv($val, $alias = null) {
	return new \wulaphp\db\sql\ImmutableValue ($val, $alias);
}

/**
 * 取当前用户的通行证.
 *
 * @param string $type 通行证类型.
 *
 * @return \wulaphp\auth\Passport
 */
function whoami($type = 'default') {
	return \wulaphp\auth\Passport::get($type);
}

/**
 * 不要调用它.
 *
 * @param Throwable $e
 *
 * @deprecated
 */
function wula_exception_handler($e) {
	global $argv;
	if (!defined('DEBUG') || DEBUG < DEBUG_ERROR) {
		if ($argv) {
			echo $e->getMessage(), "\n";
			echo $e->getTraceAsString(), "\n";
		} else {
			status_header(500);
			$stack  = [];
			$msg    = $e->getMessage();
			$tracks = $e->getTrace();

			$f = $e->getFile();
			$l = $e->getLine();
			array_unshift($tracks, ['line' => $l, 'file' => $f, 'function' => '']);
			foreach ($tracks as $i => $t) {
				$tss     = ['<tr>'];
				$tss[]   = "<td bgcolor=\"#eeeeec\" align=\"center\">$i</i>";
				$tss[]   = "<td bgcolor=\"#eeeeec\">{$t['function']}( )</td>";
				$f       = str_replace(APPROOT, '', $t['file']);
				$tss[]   = "<td bgcolor=\"#eeeeec\">{$f}<b>:</b>{$t['line']}</td>";
				$tss []  = '</tr>';
				$stack[] = implode('', $tss);
			}
			$errorFile = file_get_contents(__DIR__ . '/debug.tpl');
			$errorFile = str_replace(['{$message}', '{$stackInfo}', '{$title}', '{$tip}', '{$cs}', '{$f}', '{$l}', '{$uri}'], [$msg, implode('', $stack), __('Oops'), __('Fatal error'), __('Call Stack'), __('Function'), __('Location'), \wulaphp\router\Router::getURI()], $errorFile);
			echo $errorFile;
			exit(0);
		}
	} else {
		log_error($e->getMessage() . "\n" . $e->getTraceAsString(), 'exceptions');
		if ($argv) {
			exit(1);
		} else {
			\wulaphp\io\Response::respond(500, $e->getMessage());
		}
	}
}

/**
 * 不要调用它.
 * @deprecated
 */
function wula_shutdown_function() {
	define('WULA_STOPTIME', microtime(true));
	fire('wula\stop');
}

include WULA_ROOT . 'includes/plugin.php';
include WULA_ROOT . 'includes/kernelimpl.php';
include WULA_ROOT . 'includes/template.php';
// end of file functions.php