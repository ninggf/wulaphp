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

class RESTFulClient {
    private $url;
    private $ver;
    private $appSecret;
    private $appKey;
    private $curl;
    private $timeout = 15;
    private $cookies = [];
    private $headers = [];
    /**
     * @var \wulaphp\restful\ISignCheck
     */
    private $signer;
    public  $content;
    public  $code = 200;

    /**
     * 构建一个RESTful Client 实例.
     *
     * @param string $url
     *            the entry of the RESTful Server。
     * @param string $appKey
     *            app key.
     * @param string $appSecret
     *            app secret.
     * @param string $ver
     *            version of the API.
     * @param int    $timeout
     *            timeout.
     */
    public function __construct($url, $appKey, $appSecret, $ver = '1', $timeout = 15) {
        $this->url       = $url;
        $this->appKey    = $appKey;
        $this->appSecret = $appSecret;
        $this->ver       = $ver;
        $this->timeout   = intval($timeout);
        $this->signer    = new DefaultSignChecker();
    }

    /**
     * 析构.
     */
    public function __destruct() {
        if ($this->curl) {
            @curl_close($this->curl);
            $this->curl = null;
        }
    }

    /**
     * @param \wulaphp\restful\ISignCheck $signer
     */
    public function setSigner($signer) {
        $this->signer = $signer;
    }

    /**
     * 设置cookie。
     *
     * @param string|null $cookie k=v 当传null时，清空之前设置的cookie
     *
     * @return $this
     */
    public function cookie($cookie = null) {
        if ($cookie === null) {
            $this->cookies = [];
        } else {
            $this->cookies[] = $cookie;
        }

        return $this;
    }

    /**
     * 设置请求头.
     *
     * @param string $header null时清空所有已设header
     * @param string $value  null时清空当前header
     *
     * @return $this
     */
    public function header($header = null, $value = null) {
        if ($header === null) {
            $this->headers = [];
        } else if ($value === null) {
            unset($this->headers[ $header ]);
        } else {
            $this->headers[ $header ] = $header . ': ' . $value;
        }

        return $this;
    }

    /**
     * 使用get方法调用接口API.
     *
     * @param string $api    接口.
     * @param array  $params 参数.
     * @param int    $timeout
     *
     * @return \wulaphp\restful\RESTFulClient 接口的返回值.
     */
    public function get($api, $params = [], $timeout = null) {
        $this->prepare($params, $api);
        $url = $this->url . '?' . http_build_query($params);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPGET, 1);
        curl_setopt($this->curl, CURLOPT_UPLOAD, false);
        if (is_numeric($timeout) && $timeout) {
            $this->timeout = $timeout;
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }

        return $this;
    }

    /**
     * 批量获取.
     *
     * @param array    $apis
     * @param array    $params
     * @param null|int $timeout
     *
     * @return array
     */
    public function gets($apis, $params = [], $timeout = null) {
        $clients = [];
        foreach ($apis as $idx => $api) {
            $param           = $params[ $idx ];
            $client          = new self($this->url, $this->appKey, $this->appSecret, $this->ver, $timeout ? $timeout : $this->timeout);
            $clients[ $idx ] = $client->get($api, $param, $timeout);
        }

        return $this->execute($clients);
    }

    /**
     * 批量提交.
     *
     * @param array    $apis
     * @param array    $params
     * @param null|int $timeout
     *
     * @return array
     */
    public function posts($apis, $params = [], $timeout = null) {
        $clients = [];
        foreach ($apis as $idx => $api) {
            $param           = $params[ $idx ];
            $client          = new self($this->url, $this->appKey, $this->appSecret, $this->ver, $timeout ? $timeout : $this->timeout);
            $clients[ $idx ] = $client->post($api, $param, $timeout);
        }

        return $this->execute($clients);
    }

    /**
     * 使用POST方法调用接口API.
     *
     * @param string $api
     *            接口.
     * @param array  $params
     *            参数.
     * @param int    $timeout
     *
     * @return \wulaphp\restful\RESTFulClient  接口的返回值.
     */
    public function post($api, $params = [], $timeout = null) {
        $this->prepare($params, $api);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
        $this->preparePostData($params);

        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);

        if ($timeout && is_numeric($timeout)) {
            $this->timeout = $timeout;
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }

        return $this;
    }

    /**
     * 解析JOSN格式的返回值到array格式.
     *
     * @param string $rst
     *            JSON格式的返回值.
     *
     * @return array 结果.
     */
    public function getReturn($rst = null) {
        $statusCode = 200;
        if ($rst === null) {
            $this->content = $rst = curl_exec($this->curl);
            if ($rst === false) {
                log_warn(curl_error($this->curl), 'rest.err');
            }
            $this->code = $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            curl_close($this->curl);
            $this->curl = null;
        } else {
            $this->content = $rst;
        }
        if ($statusCode == 200) {
            $json = @json_decode($rst, true);
        } else {
            $json = false;
        }
        if ($json) {
            return $json;
        } else {
            $rtn = ['response' => ['error' => []]];
            switch ($statusCode) {
                case 200:
                    $rtn['response']['error']['code'] = 500;
                    $rtn['response']['error']['msg']  = '解析出错';
                    $rtn['response']['error']['body'] = $rst;
                    break;
                case 400:
                    $rtn['response']['error']['http_code'] = 400;
                    $rtn['response']['error']['msg']       = '错误请求，缺少api参数';
                    break;
                case 401:
                    $rtn['response']['error']['http_code'] = 401;
                    $rtn['response']['error']['msg']       = '需要登录';
                    break;
                case 403:
                    $rtn['response']['error']['http_code'] = 403;
                    $rtn['response']['error']['msg']       = '禁止访问';
                    break;
                case 404:
                    $rtn['response']['error']['http_code'] = 404;
                    $rtn['response']['error']['msg']       = 'API不存在';
                    break;
                case 405:
                    $rtn['response']['error']['http_code'] = 405;
                    $rtn['response']['error']['msg']       = '错误的请求方法';
                    break;
                case 406:
                    $rtn['response']['error']['http_code'] = 406;
                    $rtn['response']['error']['msg']       = '非法请求';
                    break;
                case 416:
                    $rtn['response']['error']['http_code'] = 416;
                    $rtn['response']['error']['msg']       = '错误的API格式';
                    break;
                case 501:
                    $rtn['response']['error']['http_code'] = 501;
                    $rtn['response']['error']['msg']       = '未实现的API';
                    break;
                case 502:
                    $rtn['response']['error']['http_code'] = 502;
                    $rtn['response']['error']['msg']       = '网关出错';
                    break;
                case 503:
                    $rtn['response']['error']['http_code'] = 503;
                    $rtn['response']['error']['msg']       = $rst;
                    break;
                case 500:
                default:
                    $rtn['response']['error']['http_code'] = 500;
                    $rtn['response']['error']['msg']       = '服务器运行出错';
            }

            return $rtn;
        }
    }

    /**
     * 将结果转换为ARRAY
     *
     * @param null|string $rst
     *
     * @return array
     */
    public function toArray($rst = null) {
        return $this->getReturn($rst);
    }

    /**
     * 将结果转换为XML结果。
     *
     * @param null|string $rst
     *
     * @return \SimpleXMLElement
     */
    public function toXml($rst = null) {
        if ($rst === null) {
            $rst = curl_exec($this->curl);
            if ($rst === false) {
                log_warn(curl_error($this->curl), 'rest.err');
            }
            curl_close($this->curl);
            $this->curl = null;
        }
        if (empty ($rst)) {
            return new \SimpleXMLElement('<response><error><code>106</code><msg>' . __('Internal error.') . '</msg></error></response>');
        } else {
            try {
                return @new \SimpleXMLElement($rst);
            } catch (\Exception $e) {
                return new \SimpleXMLElement('<response><error><code>107</code><msg>' . __('Not supported response format.') . '</msg></error></response>');
            }
        }
    }

    /**
     * 处理POST数据,主要处理上传的文件.
     *
     * @param array $data
     */
    private function preparePostData(array &$data) {
        foreach ($data as $key => &$val) {
            if (is_string($val) && $val{0} == '@' && is_file(trim(substr($val, 1), '"'))) {
                $data [ $key ] = new \CURLFile(realpath(trim(substr($val, 1), '"')));
            } else if (is_array($val)) {
                $this->preparePostData($val);
            }
        }
    }

    /**
     * 准备连接请求.
     *
     * @param array  $params
     * @param string $api
     */
    protected function prepare(&$params, $api) {
        $params ['api']     = $api;
        $params ['app_key'] = $this->appKey;
        if (!isset($params['v'])) {
            $params ['v'] = $this->ver;
        }
        if (!isset($params['sign_method'])) {
            $params['sign_method'] = 'hmac';
        }
        if (!isset($params['timestamp'])) {
            $params['timestamp'] = gmdate('Y-m-d H:i:s') . ' GMT';
        }
        if (!isset($params['format'])) {
            $params['format'] = 'json';
        }
        $params ['sign'] = $this->signer->sign($params, $this->appSecret);
        if (!$this->curl) {
            $this->curl = curl_init();
        }
        curl_setopt($this->curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, []);
        if ($this->cookies) {
            curl_setopt($this->curl, CURLOPT_COOKIE, implode('; ', $this->cookies));
        }
        if ($this->headers) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array_values($this->headers));
        }
    }

    /**
     * 并行执行请求。
     *
     * @param array $clients
     *
     * @return array results for each request.
     */
    public function execute(array $clients) {
        if ($clients) {
            $mh      = curl_multi_init();
            $handles = [];
            /**@var \wulaphp\restful\RESTFulClient $client */
            foreach ($clients as $i => $client) {
                $ch             = $client->curl;
                $handles [ $i ] = ['h' => $ch, 'c' => $client];
                curl_multi_add_handle($mh, $ch);
            }
            $active = null;
            do {
                curl_multi_exec($mh, $active);
                if ($active > 0) {
                    usleep(50);
                }
            } while ($active > 0);
            $rsts = [];
            foreach ($handles as $i => $h) {
                /**@var \wulaphp\restful\RESTFulClient $client */
                $client      = $h ['c'];
                $rsts [ $i ] = $client->getReturn(curl_multi_getcontent($client->curl));
                curl_multi_remove_handle($mh, $h ['h']);
            }
            curl_multi_close($mh);

            return $rsts;
        }

        return [];
    }
}