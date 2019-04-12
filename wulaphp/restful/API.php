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

/**
 * 接口基类.
 *
 * @package rest\classes
 */
abstract class API {
    public    $sessionId = '';
    protected $appKey;
    protected $ver;

    /**
     * API constructor.
     *
     * @param string $appKey appkey
     * @param string $ver    版本.
     */
    public function __construct($appKey, $ver = '') {
        $this->appKey = $appKey;
        $this->ver    = $ver;
    }

    /**
     * 启动设置.
     * @throws \wulaphp\restful\RestException
     * @throws \wulaphp\restful\HttpException
     * @throws \wulaphp\restful\UnauthorizedException
     */
    public function setup() {
    }

    /**
     * 销毁.
     */
    public function tearDown() {
    }

    /**
     * 返回错误信息.
     *
     * @param int|string  $code
     * @param string|null $message
     *
     * @throws \wulaphp\restful\RestException
     */
    protected final function error($code, $message = null) {
        if (empty($message) && is_string($code)) {
            $msg = explode('@', $code);
            if (count($msg) >= 2) {
                $message = $msg[1];
                $code    = intval($msg[0]);
            } else {
                $message = $code;
                $code    = 500;
            }
        } else if (empty($message)) {
            $message = __('Internal Error');
        }
        throw new RestException($message, $code);
    }

    /**
     * 未登录异常.
     * @throws \wulaphp\restful\UnauthorizedException
     */
    protected final function unauthorized() {
        throw new UnauthorizedException();
    }
}