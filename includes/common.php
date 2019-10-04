<?php
/**
 * 取数据.
 *
 * @param string $name
 * @param mixed  $default
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
function rqsts(array $names, $xss_clean = true, array $map = []) {
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
 * 取URL中的参数(仅在action中可靠).
 *
 * @param int    $pos
 * @param string $default
 *
 * @return mixed
 */
function param($pos = 0, $default = '') {
    return \wulaphp\router\Router::getRouter()->getParam($pos, $default);
}

/**
 * 取数据.
 *
 * @param string $name
 * @param mixed  $default
 *
 * @return mixed
 * @see rqst
 *
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
    global $__rqst;
    if (defined('ARTISAN_TASK_PID')) {
        $__rqst = \wulaphp\io\Request::getInstance();
    } else if (!$__rqst) {
        $__rqst = wulaphp\io\Request::getInstance();
    }

    return isset ($__rqst[ $name ]);
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
 * @param float  $default
 *
 * @return float
 */
function frqst($name, $default = 0.0) {
    return floatval(rqst($name, $default, true));
}

/**
 * 记录debug信息.
 *
 * @param string $message
 * @param string $file
 */
function log_debug($message, $file = '') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    log_message($message, DEBUG_DEBUG, $file, $trace);
}

/**
 * 记录info信息.
 *
 * @param string $message
 * @param string $file
 */
function log_info($message, $file = '') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    log_message($message, DEBUG_INFO, $file, $trace);
}

/**
 * 记录warn信息.
 *
 * @param string $message
 * @param string $file
 */
function log_warn($message, $file = '') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    log_message($message, DEBUG_WARN, $file, $trace);
}

/**
 * 记录error信息.
 *
 * @param string $message
 * @param string $file
 */
function log_error($message, $file = '') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    log_message($message, DEBUG_ERROR, $file, $trace);
}

/**
 * log.
 *
 * @param string $message
 * @param int    $level debug,info,warn,error
 * @param string $file
 * @param array  $trace_info
 *
 * @filter logger\getLogger $logger $level $file
 */
function log_message($message, $level, $file = 'wula', array $trace_info = []) {
    global $_wula_last_msg;
    /**@var \Psr\Log\LoggerInterface[][] $loggers */
    static $loggers = [];
    $_wula_last_msg = $message;
    if (!$trace_info) {
        $trace_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    }
    if (!defined('DEBUG')) {
        $dumps = '[' . gmdate('Y-m-d H:i:s') . ' GMT] ' . $message . "\n";
        for ($i = 0; $i < 10; $i++) {
            if (isset ($trace_info [ $i ]) && $trace_info [ $i ]) {
                $dumps .= \wulaphp\util\CommonLogger::getLine($trace_info[ $i ], $i);
            }
        }
        if (isset ($_SERVER ['REQUEST_URI'])) {
            $dumps .= " uri: " . $_SERVER ['REQUEST_URI'] . "\n";
        } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
            $dumps .= " script: " . implode(' ', $_SERVER ['argv']) . "\n";
        }
        @file_put_contents(LOGS_PATH . 'core_dump.log', $dumps, FILE_APPEND);

        return;
    }

    //记录关闭.
    if (DEBUG == DEBUG_OFF) {
        return;
    }

    if (!isset($loggers[ $level ][ $file ])) {
        //获取日志器.
        $log = apply_filter('logger\getLogger', new \wulaphp\util\CommonLogger($file), $level, $file);
        if ($log instanceof Psr\Log\LoggerInterface) {
            $logger = $log;
        } else {
            $logger = false;
        }
        $loggers[ $level ][ $file ] = $logger;
    }

    if ($level >= DEBUG && $loggers[ $level ][ $file ]) {
        $loggers[ $level ][ $file ]->log($level, $message, $trace_info);
    }
}

/**
 * 最后记录的日志信息.
 *
 * @return string
 */
function log_last_msg() {
    global $_wula_last_msg;

    return $_wula_last_msg ? $_wula_last_msg : '';
}

/**
 * 得到session名.
 *
 * @return string
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
 * @param mixed $obj
 *
 * @return string
 */
function get_unique_id($obj) {
    if (is_string($obj) || is_numeric($obj) || empty($obj)) {
        return $obj;
    } else if (is_array($obj)) {
        return md5(serialize($obj));
    } else if (is_object($obj) || $obj instanceof Closure) {
        return spl_object_hash($obj);
    }

    return null;
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
 * 根据宽高生成缩略图文件名.
 *
 * @param string $filename
 *                    原始文件名.
 * @param int    $w
 * @param int    $h
 * @param string $sep 分隔符.
 *
 * @return string
 */
function get_thumbnail_filename($filename, $w, $h, $sep = '-') {
    $finfo = pathinfo($filename);

    $shortname = $finfo['dirname'] . '/' . $finfo['filename'];
    $ext       = $finfo['extension'] ? '.' . $finfo['extension'] : '';
    if ($h > 0) {
        return $shortname . "{$sep}{$w}x{$h}{$ext}";
    } else {
        return $shortname . "{$sep}{$w}{$ext}";
    }
}

/**
 * 显示异常页.
 *
 * @param \Exception $exception 异常
 */
function show_exception_page($exception) {
    global $argv;
    if (defined('DEBUG') && DEBUG < DEBUG_ERROR) {
        if ($argv) {
            echo $exception->getMessage(), "\n";
            echo $exception->getTraceAsString(), "\n";
        } else if (DEBUG == DEBUG_DEBUG) {
            status_header(500);
            $stack  = [];
            $msg    = str_replace('file:' . APPROOT, '', $exception->getMessage());
            $tracks = $exception->getTrace();

            $f = $exception->getFile();
            $l = $exception->getLine();
            array_unshift($tracks, ['line' => $l, 'file' => $f, 'function' => '']);
            foreach ($tracks as $i => $t) {
                $tss     = ['<tr>'];
                $tss[]   = "<td class=\"cell-n\">$i</i>";
                $tss[]   = "<td class=\"cell-f\">{$t['function']}( )</td>";
                $f       = str_replace(APPROOT, '', $t['file']);
                $tss[]   = "<td>{$f}<b>:</b>{$t['line']}</td>";
                $tss []  = '</tr>';
                $stack[] = implode('', $tss);
            }
            $errorFile = file_get_contents(__DIR__ . '/debug.tpl');
            $errorFile = str_replace([
                '{$message}',
                '{$stackInfo}',
                '{$title}',
                '{$tip}',
                '{$cs}',
                '{$f}',
                '{$l}',
                '{$uri}'
            ], [
                $msg,
                implode('', $stack),
                __('Oops'),
                __('Fatal error'),
                __('Call Stack'),
                __('Function'),
                __('Location'),
                \wulaphp\router\Router::getURI()
            ], $errorFile);
            echo $errorFile;
            exit(0);
        } else {
            status_header(500);
            $msg  = str_replace('file:' . APPROOT, '', $exception->getMessage());
            $f    = str_replace(APPROOT, '', $exception->getFile());
            $l    = $exception->getLine();
            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head> <meta charset="utf-8">  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1,user-scalable=no"></head><body>
<br/><b>Warning</b>: $msg in <b>$f</b> on line <b>$l</b><br/>
</body></html>
HTML;
            echo $html;
            exit(0);
        }
    } else {
        log_error($exception->getMessage() . "\n" . $exception->getTraceAsString(), 'exceptions');
        if ($argv) {
            echo $exception->getMessage(), "\n";
            echo $exception->getTraceAsString(), "\n";
            exit(1);
        } else {
            \wulaphp\io\Response::respond(500, $exception->getMessage());
        }
    }
}

//异常处理
set_exception_handler('show_exception_page');
//脚本结束回调
register_shutdown_function(function () {
    define('WULA_STOPTIME', microtime(true));
    fire('wula\stop');
});
include WULA_ROOT . 'includes/plugin.php';
include WULA_ROOT . 'includes/kernelimpl.php';
include WULA_ROOT . 'includes/template.php';
// end of file functions.php