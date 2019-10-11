<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\io;
/**
 * SSDB存储器驱动.
 *
 * @package wulaphp\io
 */
class SSDBStorageDriver extends StorageDriver {
    /**
     * @var \Redis
     */
    private $ssdb;

    /**
     * @return bool
     * @throws \wulaphp\io\SSDBException
     */
    protected function initialize() {
        list($host, $port, $timeout) = get_for_list($this->options, 'host', 'port', 'timeout');
        if (!$host) {
            $host = 'localhost';
        }
        if (!$port) {
            $port = '8888';
        }
        if ($host && $port) {
            try {
                $timeout = intval($timeout);
                if (!$timeout) {
                    $timeout = 5;
                }
                $this->ssdb = new SimpleSSDB($host, $port, $timeout ? $timeout * 1000 : 2000);
            } catch (SSDBException $e) {
                throw $e;
            }

            return true;
        }

        return false;
    }

    public function save($filename, $content) {
        if ($this->ssdb) {
            try {
                $filename = md5($filename);
                $rst      = $this->ssdb->set($filename, $content);

                return $rst === false ? false : true;
            } catch (\Exception $e) {
                log_error($e->getMessage(), 'ssdb');
            }
        }

        return false;
    }

    public function load($filename) {
        if ($this->ssdb) {
            try {
                $filename = md5($filename);

                return $this->ssdb->get($filename);
            } catch (\Exception $e) {
                log_error($e->getMessage(), 'ssdb');
            }
        }

        return '';
    }

    public function delete($filename) {
        if ($this->ssdb) {
            try {
                $filename = md5($filename);
                $this->ssdb->del($filename);

                return true;
            } catch (\Exception $e) {
                log_error($e->getMessage(), 'ssdb');
            }
        }

        return false;
    }
}