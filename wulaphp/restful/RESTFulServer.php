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

use wulaphp\app\App;
use wulaphp\io\Request;
use wulaphp\io\Response;
use wulaphp\io\Session;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\View;
use wulaphp\mvc\view\XmlView;

/**
 * RESTFul 服务器.
 *
 * @package wulaphp\restful
 */
class RESTFulServer {
    protected $secretChecker;
    protected $signChecker;
    protected $debug  = false;
    protected $format;
    protected $expire = 300;
    protected $rqMehtod;
    protected $api;

    /**
     * RESTFulServer constructor.
     *
     * @param \wulaphp\restful\ISecretCheck $secretChecker  密钥校验器
     * @param \wulaphp\restful\ISignCheck   $signChecker    签名校验器,默认为DefaultSignChecker
     * @param string                        $format         默认响应格式，支持json和xml
     * @param int                           $session_expire session过期时间(单位秒)
     */
    public function __construct(ISecretCheck $secretChecker, ISignCheck $signChecker = null, string $format = 'json', int $session_expire = 300) {
        $this->secretChecker = $secretChecker;
        $this->signChecker   = $signChecker ? $signChecker : new DefaultSignChecker();
        $this->format        = $format == 'xml' ? 'xml' : 'json';
        $this->expire        = intval($session_expire);
        if (!$this->expire) {
            $this->expire = 300;
        }
    }

    /**
     * @param bool $debug
     *
     * @return null|\wulaphp\mvc\view\View
     * @throws
     */
    public function run(bool $debug = false): ?View {
        $this->debug    = $debug;
        $rqMethod       = strtolower($_SERVER ['REQUEST_METHOD']);
        $this->rqMehtod = ucfirst($rqMethod);
        $this->format   = rqst('format', $this->format);
        if (!$this->format) {
            $this->format = 'json';
        }
        $format = $this->format;
        fire('restful\startCall', time(), $format);
        $rtime     = 0;
        $timestamp = rqst('timestamp');
        //时间检测，时差正负5分钟
        if ($timestamp && preg_match('/^2\d\d\d-(1[0-2]|0[1-9])-(0[1-9]|[12]\d|3[01])\s([01][0-9]|2[0-3]):([0-5]\d):([0-5]\d)(\sGMT)?$/', $timestamp)) {
            $timestampx = @strtotime($timestamp);
            if ($timestampx !== false) {
                $rtime = $timestampx;
            }
        }
        $ctime = time();
        if (($rtime + 300) < $ctime || $rtime - 300 > $ctime) {
            $this->httpout(408, 'Request Timeout');//非法请求
        }
        //时间检测结束
        $v   = irqst('v', 1);
        $api = rqst('api');//API
        if (empty($api)) {
            $this->httpout(400, 'Miss API');
        }
        $app_key = rqst('app_key');//APPKEY
        if (empty($app_key)) {
            return $this->generateResult($format, [
                'error' => [
                    'code' => 40000,
                    'msg'  => __('miss app_key@restful')
                ]
            ]);
        }
        $apis = explode('.', $api);
        if (count($apis) != 3) {
            $this->httpout(406, __('Invalid API@restful'));
        }
        $namesapce = $apis[0];
        $module    = App::getModuleById($namesapce);
        if (!$module) {
            $this->httpout(404, __('module not found@restful'));
        }
        // downgrade support
        $cls = $this->getCls($namesapce, ucfirst($apis[1]) . 'Api', &$v);
        if ($cls) {
            /**@var API $clz */
            $clz = new $cls($app_key, $v);
            $ann = new \ReflectionObject($clz);
            $rm  = $this->rqMehtod;
            try {
                if ($rm != 'Get') {
                    $m = $ann->getMethod($apis[2] . $rm);
                } else {
                    $m = $ann->getMethod($apis[2]);
                }
            } catch (\Exception $mre) {
                try {
                    if ($rm != 'Get') {
                        $tmp = $ann->getMethod($apis[2]);
                        if ($tmp) {
                            $this->httpout(405, __('unsupport request methd@restful'));
                        }
                    }
                } catch (\Exception $e) {

                }
                $m = false;
            }
            if (!$m) {
                $this->httpout(404, __('api not found@restful'));
            }

            $params  = [];//请求参数用于签名
            $dparams = [];//调用参数
            $ps      = $m->getParameters();
            /**@var \ReflectionParameter $p */
            foreach ($ps as $p) {
                $name = $p->getName();
                if (rqset($name)) {
                    $dparams[ $name ] = $params[ $name ] = rqst($name);
                } else if ($p->isOptional()) {
                    $dparams[ $name ] = $p->getDefaultValue();
                } else if (isset($_FILES[ $name ])) {
                    $dparams[ $name ] = $_FILES[ $name ];
                } else {
                    return $this->generateResult($format, [
                        'error' => [
                            'code' => 40001,
                            'msg'  => __('Miss param: %s@restful', $name)
                        ]
                    ]);
                }
            }
            $sign_method = rqst('sign_method');
            if ($sign_method != 'md5' && $sign_method != 'sha1' && $sign_method != 'hmac') {
                return $this->generateResult($format, [
                    'error' => [
                        'code' => 40002,
                        'msg'  => __('unsupport sign methd@restful')
                    ]
                ]);
            }

            $appSecret = $this->secretChecker->check($app_key);
            if (!$appSecret) {
                return $this->generateResult($format, [
                    'error' => [
                        'code' => 40003,
                        'msg'  => __('invalid app_key@restful')
                    ]
                ]);
            }
            //签名
            $sign = rqst('sign');
            $args = array_merge($params, [
                'v'           => $v,
                'app_key'     => $app_key,
                'api'         => $api,
                'timestamp'   => $timestamp,
                'sign_method' => $sign_method
            ]);
            //响应格式
            if (rqset('format')) {
                $args['format'] = $format;
            }
            //会话
            $session = rqst('session');
            if (rqset('session')) {
                $args['session'] = $session;
            }
            $allreqs = Request::getInstance()->requests();
            unset($allreqs['sign']);
            if (($extra = array_diff_key($allreqs, $args))) {
                return $this->generateResult($format, [
                    'error' => [
                        'code'   => 40004,
                        'msg'    => __('unknown params@restful'),
                        'params' => array_keys($extra)
                    ]
                ]);
            }
            //开发模式
            $dev = $this->debug;
            if (!$dev) {
                //验签
                $sign1 = $this->signChecker->sign($args, $appSecret, 'sha1', true);
                if ($sign !== $sign1) {
                    return $this->generateResult($format, [
                        'error' => [
                            'code' => 40005,
                            'msg'  => __('invalid sign@restful')
                        ]
                    ]);
                }
            }

            if ($session) {// 启动了session
                (new Session($this->expire))->start($session);
                $clz->sessionId = $session;
            }

            try {
                $this->api = $api;
                fire('restful\callApi', $api, $ctime, $args);
                $clz->setup();
                $rtn = $m->invokeArgs($clz, $dparams);

                return $this->generateResult($format, $rtn);
            } catch (RestException $re) {
                return $this->generateResult($format, [
                    'error' => [
                        'code' => $re->getCode(),
                        'msg'  => $re->getMessage()
                    ]
                ]);
            } catch (HttpException $he) {
                $this->httpout($he->getCode(), $he->getMessage());
            } catch (UnauthorizedException $un) {
                $this->httpout(401);
            } catch (\PDOException $pe) {
                return $this->generateResult($format, [
                    'error' => [
                        'code' => 40006,
                        'msg'  => __('Internal Error - database')
                    ]
                ]);
            } catch (\Exception $e) {
                log_error('[' . $api . '] failed! ' . $e->getMessage() . "\n" . var_export($dparams, true), 'api');

                return $this->generateResult($format, ['error' => ['code' => 40007, 'msg' => $e->getMessage()]]);
            } finally {
                try {
                    $clz->tearDown();
                } catch (\Exception $e) {

                }
                fire('restful\endApi', $api, time(), $args);
            }
        }
        // Not Implemented
        $this->httpout(501);

        return null;
    }

    /**
     * 获取版本号对应类全限定名.
     *
     * @param string $namespace 命名空间
     * @param string $cls       API类名
     * @param int    $v         版本号
     *
     * @return string 全限定类名
     */
    private function getCls(string $namespace, string $cls, int $v): ?string {
        do {
            $cls = $namesapce . '\\api\\v' . $v . '\\' . $cls;
            if (class_exists($cls) && is_subclass_of($cls, API::class)) {
                return $cls;
            }
            $v --;
        } while ($v > 1);

        return null;
    }

    /**
     * 生成返回结果.
     *
     * @param string $format
     * @param array  $data
     * @param bool   $trigger
     *
     * @return \wulaphp\mvc\view\View
     */
    private function generateResult(string $format, array $data, bool $trigger = true): View {
        $etime = time();
        if ($trigger) {
            if (isset($data['error'])) {
                if ($this->api) {
                    try {
                        fire('restful\errApi', $this->api, $etime, $data);
                    } catch (\Exception $e) {
                    }
                }
                try {
                    fire('restful\callError', $etime, $data);
                } catch (\Exception $e) {
                }
            }
            try {
                fire('restful\endCall', $etime, $data);
            } catch (\Exception $e) {
            }
        }
        if ($format == 'json') {
            return new JsonView(['response' => $data]);
        } else {
            return new XmlView($data, 'response');
        }
    }

    /**
     * 输出http响应输出。
     *
     * @param string|int $status 状态
     * @param string     $message
     */
    private function httpout(int $status, string $message = '') {
        status_header($status);
        if ($message) {
            echo $message;
        }

        Response::getInstance()->close();
    }
}