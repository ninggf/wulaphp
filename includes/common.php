<?php

use wulaphp\auth\Passport;
use wulaphp\db\sql\ImmutableValue;

define('CLRF', "\r\n");
define('CLRF1', "\r\n\r\n");
/**
 * 取数据.
 *
 * @param string $name
 * @param mixed  $default
 * @param bool   $xss_clean
 *
 * @return mixed
 */
function rqst(string $name, $default = '', bool $xss_clean = true) {
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
function rqsts(array $names, bool $xss_clean = true, array $map = []): array {
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
function param(int $pos = 0, string $default = '') {
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
function arg(string $name, $default = '') {
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
function rqset(string $name): bool {
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
function irqst(string $name, int $default = 0): int {
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
function frqst(string $name, float $default = 0.0): float {
    return floatval(rqst($name, $default, true));
}

/**
 * 记录debug信息.
 *
 * @param string|array $message
 * @param string       $file
 */
function log_debug($message, string $file = 'wula') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    log_message($message, DEBUG_DEBUG, $file, []);
}

/**
 * 记录info信息.
 *
 * @param string|array $message
 * @param string       $file
 */
function log_info($message, string $file = 'wula') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    log_message($message, DEBUG_INFO, $file, []);
}

/**
 * 记录warn信息.
 *
 * @param string|array $message
 * @param string       $file
 */
function log_warn($message, string $file = 'wula') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    log_message($message, DEBUG_WARN, $file, []);
}

/**
 * 记录error信息.
 *
 * @param string|array $message
 * @param string       $file
 */
function log_error($message, string $file = 'wula') {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    log_message($message, DEBUG_ERROR, $file, $trace);
}

/**
 * log message.
 *
 * @param string|array $message
 * @param int          $level debug,info,warn,error
 * @param string       $file
 * @param array        $trace_info
 *
 * @filter logger\getLogger $logger $level $file
 */
function log_message($message, int $level, string $file = 'wula', array $trace_info = []) {
    global $_wula_last_msg;
    /**@var \Psr\Log\LoggerInterface[][] $loggers */
    static $loggers = [];
    $_wula_last_msg = $message;

    if (!defined('DEBUG')) {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $dumps = '[' . date('c') . '] ' . $message . "\n";
        for ($i = 0; $i < 10; $i ++) {
            if (isset ($trace_info [ $i ]) && $trace_info [ $i ]) {
                $dumps .= \wulaphp\util\CommonLogger::getLine($trace_info[ $i ], $i);
            }
        }
        if (isset ($_SERVER ['REQUEST_URI'])) {
            $dumps .= " uri: " . $_SERVER ['REQUEST_URI'] . "\n";
        } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
            $dumps .= " script: " . implode(' ', $_SERVER ['argv']) . "\n";
        }

        @error_log($dumps, 3, LOGS_PATH . 'bootstrap.log');

        if (PHP_SAPI != 'cli') {
            @error_log($message, 4); #将日志发送到SAPI处理器
        }

        return;
    }
    //记录关闭.
    if (DEBUG == DEBUG_OFF) {
        return;
    }
    if (LOG_DRIVER == 'redis' || LOG_DRIVER == 'fluentd') {
        $logt = '_single_log__';
    } else {
        $logt = $file;
    }
    if (!isset($loggers[ $logt ])) {
        //获取日志器.
        switch (LOG_DRIVER) {
            case 'redis':
                $dlogger = new \wulaphp\util\RedisLogger($file);
                break;
            case 'fluentd':
                $dlogger = new \wulaphp\util\FluentdLogger($file);
                break;
            case 'json':
                $dlogger = new \wulaphp\util\JsonLogger($file);
                break;
            default:
                $dlogger = new \wulaphp\util\CommonLogger($file);
        }

        $log = apply_filter('logger\getLogger', $dlogger, $file);

        if ($log instanceof Psr\Log\LoggerInterface) {
            $logger = $log;
        } else {
            $logger = null;
        }
        $loggers[ $logt ] = $logger;
    }

    if ($level >= DEBUG && $loggers[ $logt ]) {
        $loggers[ $logt ]->log($level, $message, $trace_info);
    }
}

/**
 * 最后记录的日志信息.
 *
 * @return string
 */
function log_last_msg(): string {
    global $_wula_last_msg;

    return $_wula_last_msg ? (is_array($_wula_last_msg) ? json_encode($_wula_last_msg, JSON_PRETTY_PRINT) : '') : '';
}

/**
 * 得到session名.
 *
 * @return string
 * @filter  get_session_name session_name
 */
function get_session_name(): string {
    return apply_filter('get_session_name', 'phpsid');
}

/**
 * 生成SQL中不可变字符.
 *
 * @param string      $val
 * @param string|null $alias
 *
 * @return \wulaphp\db\sql\ImmutableValue
 */
function imv(string $val, ?string $alias = null): ImmutableValue {
    return new \wulaphp\db\sql\ImmutableValue ($val, $alias);
}

/**
 * 字段引用.
 *
 * @param string $field
 *
 * @return \wulaphp\db\sql\Ref
 */
function imf(string $field): \wulaphp\db\sql\Ref {
    return new \wulaphp\db\sql\Ref($field);
}

/**
 * @param mixed $obj
 *
 * @return string
 */
function get_unique_id($obj): ?string {
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
function whoami(string $type = 'default'): Passport {
    return \wulaphp\auth\Passport::get($type);
}

/**
 * 根据宽高生成缩略图文件名.
 *
 * @param string $filename 原始文件名.
 * @param int    $w
 * @param int    $h
 * @param string $sep      分隔符.
 *
 * @return string
 */
function get_thumbnail_filename(string $filename, int $w, int $h, string $sep = '-') {
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
 * 通过套接字发送http请求
 *
 * @param resource $sock
 * @param array    $request
 * @param int|null $size
 *
 * @return false|string
 */
function http_send($sock, array $request, ?int &$size = 0) {
    if (empty($sock))
        return false;
    $packet = implode(CLRF, $request);
    $rst    = @fwrite($sock, $packet);
    if ($rst !== false && $rst > 0) {
        $i   = 100;
        $rtn = stream_get_contents($sock);
        $pos = strpos($rtn, CLRF1);
        while (!$pos && $i > 0) {//读完头部
            $i --;
            $rtn .= stream_get_contents($sock);
            $pos = strpos($rtn, CLRF1);
        }
        if (!$i) {
            return false;
        }
        if ($rtn) {
            $pos       = strpos($rtn, CLRF1);
            $headerStr = substr($rtn, 0, $pos);
            $preg      = '/Content-Length:\s+(\d+)/';
            if (preg_match($preg, $headerStr, $m)) {
                $size = $m[1];
            } else {
                return substr($rtn, $pos + strlen(CLRF1));
            }
            $rst   = substr($rtn, $pos + strlen(CLRF1));
            $size1 = strlen($rst);
            $j     = 400;
            while ($size1 < $size && $j > 0) {
                $j --;
                $rst   .= stream_get_contents($sock);
                $size1 = strlen($rst);
            }

            return $rst;
        }

        return false;
    } else {
        return false;
    }
}

/**
 * 显示异常页.
 *
 * @param \Throwable|null $exception 异常
 */
function show_exception_page(?Throwable $exception) {
    global $argv;
    if (!$exception) {
        return;
    }
    if (defined('DEBUG')) {
        if ($exception->getCode() != 404) {
            log_error($exception->getMessage(), 'error');
        }
        if ($argv) {
            echo $exception->getMessage(), "\n";
            echo $exception->getTraceAsString(), "\n";
        } else if (DEBUG == DEBUG_DEBUG) {
            status_header(500);
            $stack  = [];
            $msg    = str_replace('file:' . APPROOT, '', $exception->getMessage());
            $tracks = $exception->getTrace();
            $f      = $exception->getFile();
            $l      = $exception->getLine();
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
            \wulaphp\io\Response::respond(500, $exception->getMessage());
        }
    } else {
        if ($argv) {
            echo $exception->getMessage(), "\n";
            echo $exception->getTraceAsString(), "\n";
            exit(1);
        } else {
            @error_log($exception->getMessage(), 4);
            \wulaphp\io\Response::respond(500, $exception->getMessage());
            exit(0);
        }
    }
}

//异常处理
set_exception_handler('show_exception_page');
//脚本结束回调
register_shutdown_function(function () {
    @session_write_close();# close session
    define('WULA_STOPTIME', microtime(true));
    try {
        fire('wula\stop');
    } catch (\Exception $e) {
    }
});
include WULA_ROOT . 'includes/plugin.php';
include WULA_ROOT . 'includes/kernelimpl.php';
include WULA_ROOT . 'includes/template.php';
// end of file functions.php