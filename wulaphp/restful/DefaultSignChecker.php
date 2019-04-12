<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\restful;

class DefaultSignChecker implements ISignCheck {
    public function sign(array $args, $appSecret, $type = 'sha1', $server = false) {
        $args = self::checkArgs($args);
        if (isset($args['sign_method'])) {
            $type = $args['sign_method'];
        }
        self::sortArgs($args);
        $sign = [];
        foreach ($args as $key => $v) {
            if (is_array($v)) {
                foreach ($v as $k => $v1) {
                    if (!$server && $v1{0} == '@') {
                        $sign [] = $key . "[{$k}]" . self::getfileSha1($v1);
                    } else if ($v1 || is_numeric($v1)) {
                        $sign [] = $key . "[{$k}]" . $v1;
                    } else {
                        $sign [] = $key . "[{$k}]";
                    }
                }
            } else if (!$server && $v{0} == '@') {
                $sign [] = $key . self::getfileSha1($v);
            } else if ($v || is_numeric($v)) {
                $sign [] = $key . $v;
            } else {
                $sign [] = $key;
            }
        }
        $str = implode('', $sign);
        if ($type == 'sha1') {
            return sha1($str . $appSecret);
        } else if ($type == 'hmac') {
            return hash_hmac('sha256', $str, $appSecret);
        } else {
            return md5($str . $appSecret);
        }
    }

    /**
     * 递归对参数进行排序.
     *
     * @param array $args
     */
    public static function sortArgs(array &$args) {
        ksort($args);
        foreach ($args as $key => $val) {
            if (is_array($val)) {
                ksort($val);
                $args [ $key ] = $val;
                self::sortArgs($val);
            }
        }
    }

    /**
     * 处理上传的文件参数.
     *
     * @param array $args
     *
     * @return array mixed
     */
    private static function checkArgs(array $args) {
        if ($_FILES) {
            foreach ($_FILES as $key => $f) {
                if (is_array($f['name'])) {
                    foreach ($f['tmp_name'] as $tmp) {
                        $args[ $key ][] = self::getfileSha1('@"' . $tmp . '"');
                    }
                } else {
                    $args[ $key ] = self::getfileSha1('@"' . $f['tmp_name'] . '"');
                }
            }
        }

        return $args;
    }

    /**
     * @param $value
     *
     * @return string
     */
    private static function getfileSha1($value) {
        $file = trim(substr($value, 1), '"');
        if (is_file($file)) {
            return sha1_file($file);
        } else {
            return 'fnf';
        }
    }
}