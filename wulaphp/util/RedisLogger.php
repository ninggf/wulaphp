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

/**
 * Class RedisLogger
 * @package wulaphp\util
 * @internal
 */
class RedisLogger implements LoggerInterface {
    protected static $log_name = [
        DEBUG_INFO  => 'INFO',
        DEBUG_WARN  => 'WARN',
        DEBUG_DEBUG => 'DEBUG',
        DEBUG_ERROR => 'ERROR'
    ];
    protected        $channel  = '';
    private          $redis    = null;
    private          $app;

    public function __construct(string $file = 'wula') {
        $this->channel = 'logredis-logs';
        if ($file) {
            $this->app = rtrim($file, '.log');
        } else {
            $this->app = 'wula';
        }
        try {
            $this->redis = RedisClient::getRedis(env('redis.logger.db', 0));
        } catch (\Exception $e) {
            $msg = date('[d/M/Y:H:i:s O]') . ' ERROR RedisLogger ' . $e->getMessage();
            @error_log($msg, 3, LOGS_PATH . 'wula.log');
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
        if (!$this->redis) {
            return;
        }

        $ln                = self::$log_name [ $level ] ?? 'WARN';
        $mtm               = substr(microtime(), 1, 4);
        $msg['@timestamp'] = str_replace('+', $mtm . '+', date('c'));
        $msg['level']      = $ln;
        $msg['ip']         = Request::getIp() ?: '127.0.0.1';
        $msg['host']       = $_SERVER['SERVER_ADDR'] ?: '-';
        $msg['hostname']   = getenv('HOSTNAME', true) ?: '-';
        $msg['app']        = $this->app;

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
        try {
            $this->redis->rPush($this->channel, json_encode($msg));
        } catch (\Exception $e) {
            $msg = date('[d/M/Y:H:i:s O]') . ' ERROR RedisLogger ' . $e->getMessage();
            @error_log($msg, 3, LOGS_PATH . 'wula.log');
        }
    }
}