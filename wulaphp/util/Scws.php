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

class Scws {
    public static $dict;

    /**
     * 关键词替换
     *
     * @param string $string
     * @param string $replace
     * @param bool   $santize
     * @param string $dict 字典
     * @param string $attr 属性选项
     *
     * @return string
     */
    public static function replace($string, $replace = '***', $santize = false, $dict = '', $attr = null) {
        if ($santize) {
            $string = self::santize($string);
        }
        $keys = self::keywords($string, 500, $dict, $attr);
        if ($keys) {
            foreach ($keys as $key) {
                $string = str_replace($key, $replace, $string);
            }
        }

        return $string;
    }

    /**
     * 得到关键词列表.
     *
     * @param string $string
     * @param int    $count 分词数量
     * @param string $dict  字典
     * @param string $attr  属性选项
     *
     * @return array
     */
    public static function keywords($string = '', $count = 1000, $dict = '', $attr = null) {
        $keywords = [];
        if (extension_loaded('scws') && $string) {
            $scws1 = scws_new();
            $scws1->set_charset('utf8');
            $dict = $dict ? $dict : self::$dict;
            if ($dict && is_file($dict)) {
                @$scws1->set_dict($dict, SCWS_XDICT_XDB);
            }
            $scws1->set_multi(15);
            $keywords = self::doit($scws1, $string, $count, $attr);
            $scws1->close();
        }

        return $keywords;
    }

    /**
     * 对字符进行一般消毒.
     *
     * @param string $string
     *
     * @return string 消毒后的字符串.
     */
    public static function santize($string) {
        return preg_replace('/([^a-z\d])(\s|\*|\+|\-|_|,|\\\\|\/)+([^a-z\d])/i', '\1\3', $string);
    }

    /**
     * 分词
     *
     * @param resource   $scws
     * @param string     $string
     * @param string|int $count
     * @param string     $attr
     *
     * @return array
     */
    private static function doit($scws, $string, $count, $attr) {
        $keywords = [];
        $scws->set_duality(false);
        $scws->set_ignore(true);
        $scws->send_text($string);
        $tmp = $scws->get_tops($count, $attr);
        if ($tmp) {
            foreach ($tmp as $keyword) {
                $keywords [] = $keyword ['word'];
            }
        }

        return $keywords;
    }
}