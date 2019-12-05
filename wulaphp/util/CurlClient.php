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

use wulaphp\app\App;

/**
 * Curl 封装.
 *
 * @package wulaphp\util
 */
class CurlClient {
    private static $agent      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36';
    private        $ch;
    private        $domain     = '';
    private        $encoding   = 'UTF-8';
    private        $proxy      = null;
    private        $mcallback  = null;
    private        $inUsed     = false;
    private        $cookies    = null;
    private        $headers    = null;
    private        $useJson    = false;
    private        $timeout    = 30;
    private        $referer    = '';
    private        $customData = [];

    public $error     = null;
    public $errorCode = 0;

    protected function __construct(int $timeout = 30000, array $headers = [], string $referer = '') {
        $this->ch = curl_init();
        if ($headers) {
            $this->headers = $headers;
        }
        $this->timeout = $timeout;
        $this->referer = $referer;
        $this->initCurl();
    }

    /**
     * 设置Referer
     *
     * @param string $referer
     */
    public function setReferer(string $referer = '') {
        if ($this->ch && $referer) {
            curl_setopt($this->ch, CURLOPT_REFERER, $referer);
        }
    }

    /**
     * 设置自定义数据.
     *
     * @param array $cdata
     */
    public function setCustomData(array $cdata) {
        $this->customData = $cdata;
    }

    /**
     * 获取自定义数据.
     * @return array
     */
    public function getCustomData(): array {
        return $this->customData;
    }

    /**
     * 设置编码.
     *
     * @param string $charset
     */
    public function setCharset(string $charset = 'UTF-8') {
        $this->encoding = $charset;
        if (empty ($this->encoding)) {
            $this->encoding = 'UTF-8';
        }
    }

    /**
     * 设置代理.
     *
     * @param array|string $proxy
     */
    public function setProxy($proxy) {
        if ($proxy) {
            if (is_array($proxy)) {
                $type = @constant('CURLPROXY_' . strtoupper($proxy['type']));
                if ($type) {
                    curl_setopt($this->ch, CURLOPT_PROXYTYPE, $type);
                    $auth = $proxy['auth'];
                    if ($auth) {
                        curl_setopt($this->ch, CURLOPT_PROXYAUTH, $auth);
                    }
                    $port = intval($proxy['port']);
                    if ($port) {
                        curl_setopt($this->ch, CURLOPT_PROXYPORT, $port);
                    }
                    $host = $proxy['host'];
                    curl_setopt($this->ch, CURLOPT_PROXY, $host);
                } else {
                    curl_setopt($this->ch, CURLOPT_PROXYTYPE, 0);
                    curl_setopt($this->ch, CURLOPT_PROXY, null);
                    curl_setopt($this->ch, CURLOPT_PROXYAUTH, null);
                    curl_setopt($this->ch, CURLOPT_PROXYPORT, 0);
                }
            } else {
                $this->proxy = $proxy;
                curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
            }
        } else {
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, 0);
            curl_setopt($this->ch, CURLOPT_PROXY, null);
            curl_setopt($this->ch, CURLOPT_PROXYAUTH, null);
            curl_setopt($this->ch, CURLOPT_PROXYPORT, 0);
        }
    }

    /**
     * 完成回调.
     *
     * @param string|int $index
     *
     * @return bool
     */
    protected function onStart($index): bool {
        if ($this->mcallback instanceof CurlMultiExeCallback) {
            return $this->mcallback->onStart($index, $this->ch, $this->customData);
        }

        return true;
    }

    /**
     * 完成回调.
     *
     * @param int|string $index
     * @param string     $data
     *
     * @return mixed|null
     */
    protected function onFinish($index, string $data) {
        $this->inUsed = false;
        if ($this->mcallback instanceof CurlMultiExeCallback) {
            return $this->mcallback->onFinish($index, $data, $this->ch, $this->customData);
        }

        return $data;
    }

    /**
     * 出错回调.
     *
     * @param int|string $index
     *
     * @return mixed|null
     */
    protected function onError($index) {
        $this->inUsed = false;
        if ($this->mcallback instanceof CurlMultiExeCallback) {
            return $this->mcallback->onError($index, $this->ch, $this->customData);
        }

        return curl_error($this->ch);
    }

    /**
     * 获取curl channel.
     *
     * @return false|resource
     */
    public function getChannel() {
        return $this->ch;
    }

    /**
     * 以json格式传POST数据.
     *
     * @return $this
     */
    public function useJsonBody() {
        $this->useJson = true;

        return $this;
    }

    /**
     * 准备Client给execute方法调用.
     *
     * @param string               $url      URL
     * @param array                $data     数据
     * @param CurlMultiExeCallback $callback 回调
     *
     * @return CurlClient;
     */
    public function preparePost(string $url, array $data = [], ?CurlMultiExeCallback $callback = null) {
        if (!$this->inUsed) {
            $this->inUsed = true;
            if ($this->useJson) {
                $data                          = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $this->headers['Content-Type'] = 'application/json';
            }
            $options = [
                CURLOPT_URL         => $url,
                CURLOPT_POST        => true,
                CURLOPT_AUTOREFERER => 0,
                CURLOPT_POSTFIELDS  => $data,
                CURLOPT_HTTPGET     => 0
            ];
            curl_setopt_array($this->ch, $options);
            $this->dealCookie($this->ch);
            $this->dealHeader($this->ch);
            $this->mcallback = $callback;

            return $this;
        }

        return null;
    }

    /**
     * POST提交数据.
     *
     * @param string $url      URL
     * @param array  $data     数据
     * @param bool   $jsonData 数据类型
     *
     * @return bool|mixed
     */
    public function post(string $url, array $data, bool $jsonData = false) {
        foreach ($data as $key => $v) {
            if (is_array($v)) {
                foreach ($v as $k => $vv) {
                    if ($vv[0] == '@') {
                        $data[ $key ][ $k ] = new \CURLFile(substr($vv, 1));
                    }
                }
            } else if (is_string($v) && $v[0] == '@') {
                $data[ $key ] = new \CURLFile(substr($v, 1));
            }
        }
        $jsonData = $jsonData || $this->useJson;
        if ($jsonData) {
            $data                          = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->headers['Content-Type'] = 'application/json';
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $data
        ];

        curl_setopt_array($this->ch, $options);
        $this->dealCookie($this->ch);
        $this->dealHeader($this->ch);
        $rst = curl_exec($this->ch);
        if ($rst === false) {
            $this->error     = curl_error($this->ch);
            $this->errorCode = '500';
            $rst             = false;
        } else {
            $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
            if ($code != '200') {
                $this->error     = '[' . $code . ']' . get_status_header_desc($code);
                $this->errorCode = $code;
                $rst             = false;
            }
        }

        return $rst;
    }

    /**
     * 准备Client给execute方法使用.
     *
     * @param string               $url
     * @param CurlMultiExeCallback $callback
     *
     * @return CurlClient
     */
    public function prepareGet($url, ?CurlMultiExeCallback $callback = null) {
        if (!$this->inUsed) {
            $this->inUsed = true;
            $options      = [
                CURLOPT_URL         => $url,
                CURLOPT_POST        => 0,
                CURLOPT_AUTOREFERER => 1,
                CURLOPT_POSTFIELDS  => null,
                CURLOPT_HTTPGET     => 1
            ];
            curl_setopt_array($this->ch, $options);
            $this->dealCookie($this->ch);
            $this->dealHeader($this->ch);
            $this->mcallback = $callback;

            return $this;
        }

        return null;
    }

    /**
     * GET请求数据.
     *
     * @param string      $url   URL.
     * @param string|null $base  保存目录
     * @param bool        $reuse 重用
     * @param bool        $isImg 是否是图片
     *
     * @return bool|mixed|null|string|string[]
     */
    public function get($url, $base = null, $reuse = false, $isImg = false) {
        set_time_limit(0);
        if ($base) {
            $uinfo = CurlClient::getUrlInfo($url);
            if ($uinfo ['root'] != $this->domain) {
                $ip = rtrim($base, DS) . DS . 'o_tfs' . DS . ($uinfo ['path'] ? $uinfo ['path'] . DS : '');
            } else {
                $ip = rtrim($base, DS) . DS . ($uinfo ['path'] ? $uinfo ['path'] . DS : '');
            }

            if (!file_exists($ip)) {
                if (!@mkdir($ip, 0777, true)) {
                    return false;
                }
            }

            $tmpName = $ip . $uinfo ['file'];
            if (file_exists($tmpName)) {
                if ($reuse) {
                    return @file_get_contents($tmpName);
                } else {
                    return true;
                }
            }
        }
        $curl = $this->ch;
        $this->dealCookie($curl);
        $this->dealHeader($curl);
        curl_setopt($curl, CURLOPT_URL, $url);
        $rst = curl_exec($curl);
        if ($rst === false) {
            $this->error     = curl_error($this->ch);
            $this->errorCode = '500';
            $rst             = false;
        } else {
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($code != '200') {
                $this->error     = '[' . $code . ']' . get_status_header_desc($code);
                $this->errorCode = $code;
                $rst             = false;
            }
        }
        if ($rst) {
            if (!$isImg && $this->encoding != 'UTF-8') {
                $rst = CurlClient::convertString($rst, $this->encoding);
            }
            if (isset ($tmpName)) {
                if (!@file_put_contents($tmpName, $rst)) {
                    return false;
                }
            }
        }

        return $rst;
    }

    /**
     * 设置请求cookie并获取响应中的cookie。
     *
     * @param array $cookies
     *
     * @return $this
     */
    public function withCookies(?array &$cookies = null) {
        if ($cookies === null) {
            $cookies = [];
        }
        $this->cookies = &$cookies;

        return $this;
    }

    /**
     * 设置请求头，并获取响应头.
     *
     * @param array|null $headers
     *
     * @return $this
     */
    public function withHeaders(?array &$headers = null) {
        if ($headers === null) {
            $headers = [];
        }
        $headers = array_merge($this->headers ? $this->headers : [], $headers);

        if ($this->cookies === null) {
            $this->cookies = [];
        }
        $this->headers = &$headers;

        return $this;
    }

    /**
     * 使用指定网卡.
     *
     * @param string $eth
     *
     * @return $this
     */
    public function useAdapter(string $eth) {
        curl_setopt($this->ch, CURLOPT_INFILE, $eth);

        return $this;
    }

    public function userAgent(string $agent) {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);

        return $this;
    }

    /**
     * 设置代理.
     *
     * @param $proxy
     *
     * @return $this
     */
    public function useProxy(string $proxy) {
        $this->setProxy($proxy);

        return $this;
    }

    /**
     * 设置代理.
     *
     * @param array $proxy
     *
     * @return $this
     */
    public function useProxyByAry(array $proxy) {
        $this->setProxy($proxy);

        return $this;
    }

    /**
     * 设置referer。
     *
     * @param string $referer
     *
     * @return $this
     */
    public function referer(string $referer) {
        curl_setopt($this->ch, CURLOPT_REFERER, $referer);

        return $this;
    }

    /**
     * ajax方式请求.
     *
     * @return $this
     */
    public function ajax() {
        $this->headers['X-Requested-With'] = 'XMLHttpRequest';

        return $this;
    }

    /**
     * 下载图片.
     *
     * @param string $url
     * @param null   $base
     *
     * @return bool|mixed|string|string[]|null
     */
    public function getImage($url, $base = null) {
        return $this->get($url, $base, false, true);
    }

    /**
     * 设置域名.
     *
     * @param $domain
     */
    public function setDomain(string $domain) {
        $this->domain = $domain;
    }

    /**
     * 关闭
     */
    public function close() {
        if ($this->ch) {
            @curl_close($this->ch);
            $this->ch = null;
        }
    }

    /**
     * 重置客户端设置。
     */
    public function reset() {
        if ($this->ch) {
            curl_reset($this->ch);
        } else {
            $this->ch = curl_init();
        }
        $this->headers    = null;
        $this->cookies    = null;
        $this->useJson    = false;
        $this->domain     = '';
        $this->inUsed     = false;
        $this->customData = [];
        $this->initCurl();

        return true;
    }

    /**
     * 处理Cookie.
     *
     * @param resource $curl
     */
    private function dealCookie($curl) {
        if (is_array($this->cookies)) {
            if ($this->cookies) {
                $tmp_ary = [];
                foreach ($this->cookies as $name => $val) {
                    $name       = trim($name);
                    $tmp_ary [] = $name . '=' . urlencode($val->value);
                }
                $cks = implode('; ', $tmp_ary);
                curl_setopt($curl, CURLOPT_COOKIE, $cks);
            }

            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($c, $header) {
                $len     = strlen($header);
                $headers = explode(':', $header, 2);
                if (count($headers) < 2) { // ignore invalid headers
                    return $len;
                }
                $name = trim($headers[0]);
                if ($name == 'Set-Cookie') {
                    $cookie                        = trim($headers[1]);
                    $this->headers['Set-Cookie'][] = $cookie;

                    if (preg_match('#^([^;]+?)=([^;]+)(;.*)?#', $cookie, $cok)) {
                        $ck                       = new \stdClass();
                        $ck->value                = urldecode($cok[2]);
                        $ck->option               = trim($cok[3], '; ');
                        $this->cookies[ $cok[1] ] = $ck;
                    }
                } else {
                    $this->headers[ $name ] = trim($headers[1]);
                }

                return $len;
            });
        }
    }

    /**
     * 设置请求头.
     *
     * @param resource $curl
     */
    private function dealHeader($curl) {
        if ($this->headers) {
            $headers = $this->headers;
            array_walk($headers, function (&$v, $k) {
                if (!is_numeric($k)) {
                    $v = "$k: " . $v;
                }
            });
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $this->headers = [];
        }
    }

    private function initCurl() {
        $curl = $this->ch;
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        if ($this->referer) {
            curl_setopt($curl, CURLOPT_REFERER, $this->referer);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($curl, CURLOPT_USERAGENT, self::$agent);

        $proxy = App::cfg('proxy.type');
        if ($proxy) {
            $proxy = array_merge([
                'auth' => '',
                'port' => 0,
                'host' => ''
            ], App::acfg('proxy'));
            $this->setProxy($proxy);
        }
    }

    /**
     * 并行执行CurlClient请求。
     *
     * @param array $clients array(CurlClient,...)。
     *
     * @return array results for each request. array(0=>success array,1=>failed array,2=>not start).
     */
    public static function execute($clients) {
        $result = [0 => [], 1 => [], 2 => []];
        if ($clients) {
            $mh      = curl_multi_init();
            $handles = [];
            foreach ($clients as $i => $client) {
                if (!($client instanceof CurlClient)) {
                    $result[1][ $i ] = 'client is not a instance of CurlClient';
                    continue;
                }
                if ($client->onStart($i)) {
                    $ch             = $client->ch;
                    $handles [ $i ] = ['h' => $ch, 'c' => $client];
                    curl_multi_add_handle($mh, $ch);
                } else {
                    $result [2] [ $i ] = true;
                }
            }
            if (!empty ($handles)) {
                $active = null;
                do {
                    curl_multi_exec($mh, $active);
                    if ($active > 0) {
                        usleep(10);
                    }
                } while ($active > 0);

                foreach ($handles as $i => $h) {
                    /** @var \wulaphp\util\CurlClient $clt */
                    $clt = $h ['c'];
                    $rtn = curl_multi_getcontent($h ['h']);
                    if ($rtn === false) {
                        $result [1] [ $i ] = $clt->onError($i);
                    } else {
                        $result [0] [ $i ] = $clt->onFinish($i, $rtn);
                    }
                    curl_multi_remove_handle($mh, $h ['h']);
                    $clt->close();
                }
            }
            curl_multi_close($mh);
        }

        return $result;
    }

    /**
     * 获取一个Clientp实例.
     *
     * @param int   $timeout
     * @param array $headers
     * @param bool  $referer
     *
     * @return \wulaphp\util\CurlClient
     */
    public static function getClient($timeout = 60, $headers = [], $referer = false): CurlClient {
        return new CurlClient ($timeout, $headers, $referer);
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public static function getUrlInfo($url): array {
        $here = explode('/', $url);
        array_pop($here);
        $here = implode('/', $here) . '/';
        $root = preg_replace('#^((htt|ft)ps?://.+?/).*#i', '\1', $here);
        $path = str_replace($root, '', $here);
        $file = str_replace($here, '', $url);
        $pos  = strpos($file, '?');
        if ($pos) {
            $file = substr($file, 0, $pos);
        }
        $pos = strpos($file, '#');
        if ($pos) {
            $file = substr($file, 0, $pos);
        }
        if (isset ($_SESSION ['babaurl_mapping'])) {
            $mapping = $_SESSION ['babaurl_mapping'];
            $k       = md5($url);
            if ($mapping && isset ($mapping [ $k ])) {
                $file = $mapping [ $k ];
            }
        }

        return ['root' => $root, 'here' => $here, 'path' => trim($path, '/'), 'file' => $file];
    }

    /**
     * 解析URL。
     *
     * @param string $url
     * @param array  $info
     *
     * @return string
     */
    public static function getURL($url, $info): string {
        if (preg_match('#^(htt|ft)ps?://.+#i', $url)) {
            return $url;
        } else if ($url[0] === '/') {
            $root = $info ['root'];

            return $root . ltrim($url, '/');
        } else {
            return $info ['here'] . $url;
        }
    }

    public static function getScripts($content) {
        if (preg_match_all('#<script[^>]+?src\s*=\s*[\'"](.+?)[\'"][^>]*?>#imus', $content, $ms, PREG_PATTERN_ORDER)) {
            return $ms [1];
        }

        return [];
    }

    public static function getPages($content, $base) {
        $pages = [];
        if (preg_match_all('#<a[^>]+?href\s*=\s*[\'"](.+?)[\'"][^>]*?>#imus', $content, $ms, PREG_PATTERN_ORDER)) {
            $pages = $ms [1];
        }
        if ($pages) {
            $newPages = [];
            $urlinfo  = self::getUrlInfo($base);
            foreach ($pages as $p) {
                $newPages [] = self::getURL($p, $urlinfo);
            }
            $pages = $newPages;
        }

        return $pages;
    }

    public static function getImages($content) {
        $imgs = [];
        if (preg_match_all('#<img[^>]+?src\s*=\s*[\'"](.+?)[\'"][^>]*?>#imus', $content, $ms, PREG_PATTERN_ORDER)) {
            foreach ($ms [1] as $img) {
                if (preg_match('/.*data:image.+/i', $img)) {
                    continue;
                }
                $imgs [] = $img;
            }
        }
        if (preg_match_all('#url\s*\((?![\s\'"]*data:)[\'"]?(.+?)[\'"]?\s*\)#ims', $content, $ms1, PREG_PATTERN_ORDER)) {
            $imgs = array_merge($imgs, $ms1 [1]);
        }

        return $imgs;
    }

    public static function getLinks($content) {
        if (preg_match_all('#<link[^>]+?href\s*=\s*[\'"](.+?)[\'"][^>]*?>#imus', $content, $ms, PREG_PATTERN_ORDER)) {
            return $ms [1];
        }

        return [];
    }

    public static function getStyles($content) {
        if (preg_match_all('#<style[^>]*?>(.+?)</style>#imus', $content, $ms, PREG_PATTERN_ORDER)) {
            return $ms [1];
        }

        return [];
    }

    public static function getImageFromCss($content) {
        if (preg_match_all('#url\s*\((?!\s*data:)[\'"]?(.+?)[\'"]?\s*\)#ims', $content, $ms, PREG_PATTERN_ORDER)) {
            return $ms [1];
        }

        return [];
    }

    public static function convertString($str, $encoding) {
        if ($encoding != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encoding);
        } else {
            $str = mb_convert_encoding($str, 'UTF-8');
        }

        return $str;
    }
}