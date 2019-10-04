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
    protected static $log_name = [
        DEBUG_INFO  => 'INFO',
        DEBUG_WARN  => 'WARN',
        DEBUG_DEBUG => 'DEBUG',
        DEBUG_ERROR => 'ERROR'
    ];
    protected $channel     = '';

    public function __construct(string $file = 'wula') {
        $this->channel = $file;
    }

    public function emergency($message, array $context = []) {
        $this->log(DEBUG_ERROR, $message, $context);
    }

    public function alert($message, array $context = []) {
        $this->log(DEBUG_ERROR, $message, $context);
    }

    public function critical($message, array $context = []) {
        $this->log(DEBUG_ERROR, $message, $context);
    }

    public function error($message, array $context = []) {
        $this->log(DEBUG_ERROR, $message, $context);
    }

    public function warning($message, array $context = []) {
        $this->log(DEBUG_WARN, $message, $context);
    }

    public function notice($message, array $context = []) {
        $this->log(DEBUG_INFO, $message, $context);
    }

    public function info($message, array $context = []) {
        $this->log(DEBUG_INFO, $message, $context);
    }

    public function debug($message, array $context = []) {
        $this->log(DEBUG_DEBUG, $message, $context);
    }

    public function log($level, $message, array $trace_info = []) {
        $file = $this->channel;

        $ln = isset(self::$log_name [ $level ]) ? self::$log_name [ $level ] : 'WARN';
        if (defined('ARTISAN_TASK_PID')) {
            $pid = ARTISAN_TASK_PID;
        } else {
            $pid = 0;
        }
        $msg = date("Y-m-d H:i:s") . " [$pid] [$ln] {$message}\n";
        $msg .= self::getLine($trace_info[0], 0);
        if ($level > DEBUG_WARN) {//只有error的才记录trace info.
            for ($i = 1; $i < 5; $i++) {
                if (isset ($trace_info [ $i ]) && $trace_info [ $i ]) {
                    $msg .= self::getLine($trace_info[ $i ], $i);
                }
            }
            if (isset ($_SERVER ['REQUEST_URI'])) {
                $msg .= " uri: " . $_SERVER ['REQUEST_URI'] . "\n";
            } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
                $msg .= " script: " . implode(' ', $_SERVER ['argv']) . "\n";
            }
        }

        $dest_file = $file ? $file . '.log' : 'wula.log';
        @error_log($msg, 3, LOGS_PATH . $dest_file);
    }

    /**
     * 格式化堆栈信息。
     *
     * @param array $info
     * @param int   $i
     *
     * @return string
     */
    public static function getLine(array $info, int $i): string {
        if (isset($info['class'])) {
            $cls = "{$info['class']}{$info['type']}";
        } else {
            $cls = '';
        }
        $file = str_replace(APPROOT, '', $info['file']);
        if ($i) {
            return " #{$i} {$file}({$info['line']}): {$cls}{$info['function']}()\n";
        } else {
            return " #{$i} {$file}({$info['line']})\n";
        }
    }
}