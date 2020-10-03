<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;

use Psr\Log\LoggerInterface;
use wulaphp\io\Request;

class JsonLogger implements LoggerInterface {
    protected static $log_name = [
        DEBUG_INFO  => 'INFO',
        DEBUG_WARN  => 'WARN',
        DEBUG_DEBUG => 'DEBUG',
        DEBUG_ERROR => 'ERROR'
    ];
    protected        $channel  = '';
    protected        $fluentd;
    protected        $app;

    public function __construct(string $file = 'wula') {
        if ($file) {
            $this->channel = rtrim($file, '.log');
        } else {
            $this->channel = 'wula';
        }
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
        $file              = $this->channel;
        $ln                = self::$log_name [ $level ] ?? 'WARN';
        $mtm               = substr(microtime(), 1, 4);
        $msg['@timestamp'] = str_replace('+', $mtm . '+', date('c'));
        $msg['level']      = $ln;
        $msg['ip']         = Request::getIp() ?: '127.0.0.1';
        $msg['app']        = $file;
        $msg['host']       = $_SERVER['SERVER_ADDR'] ?: '-';
        $msg['hostname']   = getenv('HOSTNAME', true) ?: '-';
        if (is_array($message)) {
            $msg = array_merge($message, $msg);
        } else {
            $msg['message'] = $message;
        }
        if (isset ($_SERVER ['REQUEST_URI'])) {
            $msg['uri'] = $_SERVER ['REQUEST_URI'];
        } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
            $msg['script'] = implode(' ', $_SERVER ['argv']);
        }

        $stacks = [];
        if ($level > DEBUG_INFO && $trace_info) {//只有error的才记录trace info.
            $stacks[] = CommonLogger::getLine($trace_info[0], 0);
            for ($i = 1; $i < 5; $i ++) {
                if (isset ($trace_info [ $i ]) && $trace_info [ $i ]) {
                    $stacks[] = CommonLogger::getLine($trace_info[ $i ], $i);
                }
            }
        }

        $msg['stacks'] = implode("\n", $stacks);

        if (LOG_ROTATE) {
            $dest_file = 'app-' . date('Y-m-d') . '.json';
        } else {
            $dest_file = 'app.json';
        }

        $msg = json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @error_log($msg . "\n", 3, LOGS_PATH . $dest_file);
    }
}