<?php

use wulaphp\auth\Passport;
use wulaphp\db\sql\ImmutableValue;
use wulaphp\db\sql\Ref;
use wulaphp\io\Request;
use wulaphp\io\Response;
use wulaphp\router\Router;
use wulaphp\util\CommonLogger;
use wulaphp\util\FluentdLogger;
use wulaphp\util\JsonLogger;
use wulaphp\util\RedisLogger;

define('CLRF', "\r\n");
define('CLRF1', "\r\n\r\n");
//errpr page template
defined('WULA_ERROR_PAGE_TPL') or define('WULA_ERROR_PAGE_TPL', <<<'ERP'
<!DOCTYPE html">
<html"><head> <meta content="text/html; charset=utf-8" http-equiv="Content-Type"> <title>{{title}}</title><style type="text/css">
*{ padding: 0; margin: 0; } html{ overflow-y: scroll; } body{ background: #fff; font-family: Helvetica Neue,Helvetica,PingFang SC,Tahoma,Arial,sans-serif; color: #333; font-size: 16px; } .error{ padding: 24px 48px; } h1{ font-size: 28px; line-height: 38px; } .error .content{ padding-top: 10px} .error .info{ margin-bottom: 12px; } .error .info .title{ margin-bottom: 3px; } .error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; } .error .info .text{ line-height: 24px; } .copyright{ padding:12px 48px; color: #999; } .copyright a{ color: #000; text-decoration: none; } </style>
</head><body><div class="error"><h1>{{message}}</h1>
<div class="content">
	<div class="info"><div class="title"><h3>{{Position}}</h3></div><div class="text"><p>{{ocurPos}}</p></div></div>
	<div class="info"><div class="title"><h3>{{TRACE}}</h3></div><div class="text"><p>{{traceStr}}</p></div></div>
</div></div><div class="copyright">
<p><a href="https://www.wulaphp.com/">WULAPHP</a><sup>{{ver}}</sup>&nbsp;&nbsp;[ Make you a happy PHPer! ]</p>
</div></body></html>
ERP
);

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
        $__rqst = Request::getInstance();
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
        $__rqst = Request::getInstance();
    } else if (!$__rqst) {
        $__rqst = Request::getInstance();
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
    return Router::getRouter()->getParam($pos, $default);
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
        $__rqst = Request::getInstance();
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
        $__rqst = Request::getInstance();
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
    return intval(rqst($name, $default));
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
    return floatval(rqst($name, $default));
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
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    log_message($message, DEBUG_DEBUG, $file, $trace);
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
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    log_message($message, DEBUG_INFO, $file, $trace);
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
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    log_message($message, DEBUG_WARN, $file, $trace);
}

/**
 * 记录error信息.
 *
 * @param string|array $message
 * @param string       $file
 * @param array|null   $trace
 */
function log_error($message, string $file = 'wula', ?array $trace = null) {
    if (defined('DEBUG') && DEBUG == DEBUG_OFF) {
        return;
    }
    if (!$trace) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    }
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
        for ($i = 0; $i < 5; $i ++) {
            if (!isset ($trace_info [ $i ])) {
                break;
            }

            $dumps .= CommonLogger::getLine($trace_info[ $i ], $i);
        }
        if (isset ($_SERVER ['REQUEST_URI'])) {
            $dumps .= " #@ uri: " . $_SERVER ['REQUEST_URI'] . "\n";
        } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
            $dumps .= " #@ script: " . implode(' ', $_SERVER ['argv']) . "\n";
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
                $dlogger = new RedisLogger($file);
                break;
            case 'fluentd':
                $dlogger = new FluentdLogger($file);
                break;
            case 'json':
                $dlogger = new JsonLogger($file);
                break;
            default:
                $dlogger = new CommonLogger($file);
        }
        if (function_exists('apply_filter')) {
            $log = apply_filter('logger\getLogger', $dlogger, $file);
        } else {
            $log = $dlogger;
        }
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
    return new ImmutableValue ($val, $alias);
}

/**
 * 字段引用.
 *
 * @param string $field
 *
 * @return \wulaphp\db\sql\Ref
 */
function imf(string $field): Ref {
    return new Ref($field);
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
    return Passport::get($type);
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
function get_thumbnail_filename(string $filename, int $w, int $h, string $sep = '-'): string {
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
 * 输出http响应输出。
 *
 * @param int    $status 状态
 * @param string $message
 */
function http_out(int $status, string $message = '') {
    http_response_code($status);
    if ($message) {
        echo $message;
    }
    exit();
}

/**
 * 深度合并
 *
 * @param array $old
 * @param array $settings
 *
 * @author Leo Ning <windywany@gmail.com>
 * @date   2021-06-15 18:01:25
 * @since  1.0.0
 */
function ary_deep_merge(array &$old, array $settings) {
    if (empty($old)) {
        $old = $settings;

        return;
    }
    foreach ($settings as $key => $setting) {
        if (is_array($setting)) {
            if (isset($old[ $key ])) {
                ary_deep_merge($old[ $key ], $setting);
            } else {
                $old[ $key ] = $setting;
            }
        } else {
            $old[ $key ] = $setting;
        }
    }
}

/**
 * 打印数据验证异常.
 *
 * @param \wulaphp\validator\ValidateException $exception
 */
function print_invalid_msg(\wulaphp\validator\ValidateException $exception) {
    @ob_start();
    http_response_code(422);
    @header('Content-type: ' . RESPONSE_ACCEPT);
    $errors          = $exception->getErrors();
    $data['errors']  = $errors;
    $data['message'] = implode("\n", array_values($errors));
    $data['code']    = 422;
    echo json_encode($data);
    @ob_end_flush();
}

/**
 * 打印异常信息.
 *
 * @param \Throwable|null $exception
 * @param int             $status
 */
function print_exception(?Throwable $exception, int $status = 500) {
    if (!$exception) {
        return;
    }
    @ob_start();
    if ($status > 0) {
        $code = $exception->getCode();
        if (get_status_header_desc($code)) {
            $status = $code;
        }
        http_response_code($status);//输出响应码
    }
    @header('Content-type: ' . RESPONSE_ACCEPT);
    $ocurPos = trim(CommonLogger::getLine([
        'file' => str_replace(APPROOT, '', $exception->getFile()) . ' ',
        'line' => $exception->getLine()
    ], - 1));
    $traces  = str_replace(APPROOT, '', $exception->getTraceAsString());

    if (strtolower(RESPONSE_ACCEPT) == 'application/json') {
        $traces         = explode("\n", $traces);
        $msg['code']    = $exception->getCode() ?: 1;
        $msg['message'] = $exception->getMessage();
        if (DEBUG == DEBUG_DEBUG) {
            array_unshift($traces, $ocurPos);
            $msg['trace'] = $traces;
        }
        echo json_encode($msg);
    } else {
        $traces             = html_escape($traces);
        $traces             = explode("\n", $traces);
        $ss['{{title}}']    = __('Error Page');
        $ss['{{Position}}'] = __('Position');
        $ss['{{message}}']  = html_escape(str_replace(APPROOT, '', $exception->getMessage()));
        $ss['{{ocurPos}}']  = $ocurPos;
        $ss['{{TRACE}}']    = __('TRACE');
        $ss['{{traceStr}}'] = implode('<br/>', $traces);
        $ss['{{ver}}']      = WULA_VERSION . (WULA_RELEASE != '' ? '-' . WULA_RELEASE : '');
        echo str_replace(array_keys($ss), array_values($ss), WULA_ERROR_PAGE_TPL);
    }
    @ob_end_flush();
}

// 用户可以选择自己处理异常
$_oldExceptionHandler = set_exception_handler(null);
//异常处理
set_exception_handler(function (?Throwable $exception) use ($_oldExceptionHandler) {
    global $argv;
    try {
        defined('DEBUG') or define('DEBUG', DEBUG_DEBUG);
        if ($_oldExceptionHandler && $_oldExceptionHandler($exception)) {
            return;
        }
        //处理数据校验异常
        if ($exception instanceof \wulaphp\validator\ValidateException) {
            print_invalid_msg($exception);

            return;
        }
        try {
            if ($exception->getCode() != 404 && DEBUG > DEBUG_WARN) {
                $trace = $exception->getTrace();
                array_unshift($trace, [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]);
                log_error($exception->getMessage(), 'error', $trace);
            }
        } catch (Throwable $le) {
        }
        if ($argv) { # 命令行
            echo $exception->getMessage(), "\n";
            echo $exception->getTraceAsString(), "\n";
        } else if (DEBUG < DEBUG_INFO || !class_exists('wulaphp\io\Response')) {
            print_exception($exception);
        } else {
            Response::respond(500, $exception->getMessage());
        }
    } catch (Throwable $te) {
        print_exception($te);
    }
});

//脚本结束回调
register_shutdown_function(function () {
    define('WULA_STOPTIME', microtime(true));
    try {
        fire('wula\stop');
    } catch (\Exception $e) {
    }
    @session_write_close();# close session
});
include WULA_ROOT . 'includes/plugin.php';
include WULA_ROOT . 'includes/kernelimpl.php';
include WULA_ROOT . 'includes/template.php';
// end of file functions.php