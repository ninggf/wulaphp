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

use Fluent\Logger\FluentLogger;
use Psr\Log\LoggerInterface;
use wulaphp\io\Request;

class FluentdLogger implements LoggerInterface {
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
        $this->app                  = $file ?: 'wula';
        $this->channel              = env('app.name', 'wulaphp');
        $opts['max_write_retry']    = @intval(env('fluentd.retry', 3)) ?: 3;
        $opts['socket_timeout']     = @intval(env('fluentd.timeout', 3)) ?: 3;
        $opts['connection_timeout'] = @intval(env('fluentd.con.timeout', 3)) ?: 3;
        $this->fluentd              = FluentLogger::open(env('fluentd.host', 'localhost'), env('fluentd.port', 24224), $opts);
        $this->fluentd->registerErrorHandler(function ($logger, $entity, $error) {
            $msg = date('[d/M/Y:H:i:s O]');
            $msg .= sprintf(" ERROR FluentdLogger %s %s: %s", $error, $entity->getTag(), json_encode($entity->getData()));
            @error_log($msg, 3, LOGS_PATH . 'wula.log');
        });
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
        if (!$this->fluentd) {
            return;
        }

        $ln = isset(self::$log_name [ $level ]) ? self::$log_name [ $level ] : 'WARN';

        $msg['@timestamp'] = date("c");
        $msg['level']      = $ln;
        $msg['ip']         = Request::getIp() ?: '-';
        $msg['message']    = $message;
        $msg['host']       = $_SERVER['SERVER_ADDR'] ?: '-';
        $msg['app']        = $this->app;

        if (isset ($_SERVER ['REQUEST_URI'])) {
            $msg['uri'] = $_SERVER ['REQUEST_URI'];
        } else if (isset($_SERVER['argc']) && $_SERVER['argc']) {
            $msg['script'] = implode(' ', $_SERVER ['argv']);
        }

        $stacks = [];
        if ($level > DEBUG_WARN) {//只有error的才记录trace info.
            $stacks[] = self::getLine($trace_info[0], 0);
            for ($i = 1; $i < 5; $i ++) {
                if (isset ($trace_info [ $i ]) && $trace_info [ $i ]) {
                    $stacks[] = self::getLine($trace_info[ $i ], $i);
                }
            }
        }

        $msg['stacks'] = implode("\n", $stacks);

        try {
            $tag = $this->channel . '.' . strtolower($ln);
            $this->fluentd->post($tag, $msg);
        } catch (\Exception $e) {
            $msg = date('[d/M/Y:H:i:s O]') . ' ERROR FluentdLogger ' . $e->getMessage();
            @error_log($msg, 3, LOGS_PATH . 'wula.log');
        }
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