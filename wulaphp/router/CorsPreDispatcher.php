<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\router;

use wulaphp\app\App;
use wulaphp\io\Response;

class CorsPreDispatcher implements IURLPreDispatcher {
    public function preDispatch($url, $router, $view) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $host   = $_SERVER['HTTP_HOST'] ?? false;
        if (!$host || preg_replace('#^https?://#', '', $origin) == $host) {
            return null;
        }
        //开启CORS且请求头中有ORIGIN
        if ($origin && ($cors = App::acfg('cors'))) {
            $defaultCorsHeaders = [
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Methods' => 'GET,HEAD,POST',
                'Access-Control-Allow-Headers' => '*',
                'Access-Control-Max-Age'       => '1800',
            ];
            $corsHeaders        = null;
            $allowedOrigin      = null;
            $uri                = '/' . $url;
            $rq                 = strtolower($_SERVER['REQUEST_METHOD']);
            foreach ($cors as $pattern => $headers) {
                $pattern = str_replace('*', '.+?', $pattern);
                if (preg_match('#^' . $pattern . '$#', $uri)) {
                    $headers = array_merge($defaultCorsHeaders, $headers);
                    //源检测
                    if (($allowedOrigin = $headers['Access-Control-Allow-Origin'] ?? null) && $allowedOrigin != '*' && !in_array($origin, preg_split('#\s*,\s*#', $allowedOrigin))) {
                        break;
                    }
                    // 请求方法检测
                    $allowedMethod = $headers['Access-Control-Allow-Methods'] ?? null;
                    if ($allowedMethod != '*') {
                        $am   = preg_split('#\s*,\s*#', strtolower($allowedMethod));
                        $am[] = 'options';
                        if (!in_array($rq, $am)) {
                            break;
                        }
                    }
                    // 安全检测
                    if ((!empty($_COOKIE) || isset($_SERVER['HTTP_AUTHORIZATION'])) && !isset($headers['Access-Control-Allow-Credentials'])) {
                        break;
                    }

                    $corsHeaders = $headers;
                    break;
                }
            }

            //处理响应头
            if ($corsHeaders) {
                if ($rq == 'options') {
                    foreach ($headers as $header => $value) {
                        if ($header == 'Access-Control-Allow-Origin') {
                            @header($header . ': ' . $origin);
                        } else if ($header == 'Access-Control-Allow-Credentials') {
                            @header($header . ': true');
                        } else {
                            @header($header . ': ' . $value);
                        }
                    }
                    http_response_code(204);
                    exit();//可以直接返回204
                } else {
                    if (isset($corsHeaders['Access-Control-Allow-Credentials'])) {
                        @header('Access-Control-Allow-Credentials: true');
                    }
                    @header('Access-Control-Allow-Origin: ' . $origin);
                    @header('Vary: origin');
                }
            } else {
                Response::respond(403);
            }
        }

        return null;
    }
}