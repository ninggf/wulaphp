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
 * 本地存储器.
 *
 * @package wulaphp\io
 */
class LocaleStorage extends StorageDriver {
    private $dir;

    protected function initialize() {
        list($ssn) = get_for_list($this->options, 'path');
        if (!$ssn) {
            return false;
        }
        $ssn = str_replace('/', DS, $ssn);
        if ($ssn{0} == DS) {
            $this->dir = $ssn;
        } else {
            $this->dir = APPROOT . $ssn . DS;
        }
        if (!is_dir($this->dir)) {
            return @mkdir($this->dir, 0755, true);
        }

        return true;
    }

    public function save($filename, $content) {
        $rfn = $this->realname($filename);
        if ($rfn) {
            return @file_put_contents($rfn, $content);
        }

        return false;
    }

    public function load($filename) {
        $rfn = $this->realname($filename);
        if ($rfn) {
            return @file_get_contents($rfn);
        }

        return '';
    }

    public function delete($filename) {
        $rfn = $this->realname($filename);
        if ($rfn && is_file($rfn)) {
            return @unlink($rfn);
        }

        return true;
    }

    private function realname($filename) {
        $fs = explode('/', ltrim($filename, '/'), 2);
        if (count($fs) == 2) {
            $dir = $this->dir . $fs[0] . DS;
        } else {
            $dir = $this->dir;
        }
        $fn  = md5($filename);
        $dir = $dir . substr($fn, 0, 2) . DS . substr($fn, 2, 2) . DS;
        if (is_dir($dir) || @mkdir($dir, 0755, true)) {
            return $dir . $fn;
        }

        return false;
    }
}