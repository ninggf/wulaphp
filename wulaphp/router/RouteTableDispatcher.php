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
use wulaphp\io\Session;

/**
 * 基于路由表的分发器.
 *
 * @package wulaphp\router
 */
class RouteTableDispatcher implements IURLDispatcher {
    public function dispatch($url, $router, $parsedInfo) {
        $route = RouteTableDispatcher::parseURL($parsedInfo);
        //解析不了
        if (!$route) {
            return null;
        }
        $controllers = explode('/', $route);
        $len         = count($controllers);
        if ($len < 2) {//无法路由
            return null;
        }
        $module    = array_shift($controllers);
        $module    = strtolower($module);
        $namespace = App::dir2id($module, true);
        if (!$namespace) {
            return null;
        }
        //加载路由表
        $rtable = MODULE_ROOT . $module . DS . 'route.php';
        if (is_file($rtable)) {
            $routes = include $rtable;
            $uk     = implode('/', $controllers);
            if ($routes && isset($routes[ $uk ])) {
                //['template' => 'abc.tpl', 'expire' => 100, 'func' => '','Content-Type'=>'text/html','data'=>[],'session'=>true]
                $route = $routes[ $uk ];
                if (isset($route['template']) && $route['template']) {
                    $expire = intval(aryget('expire', $route), 0);
                    $func   = aryget('func', $route);
                    $data   = isset($route['data']) ? (array)$route['data'] : [];
                    if (isset($route['session']) && $route['session']) {
                        (new Session())->start();
                    }
                    if ($func && is_callable($func)) {
                        $data = $func(...[$data, $parsedInfo->page]);
                    }
                    $data            = is_array($data) ? $data : ['result' => $data];
                    $data['urlInfo'] = $parsedInfo;
                    if ($expire > 0) {
                        define('CACHE_EXPIRE', $expire);
                    }
                    if (isset($route['Content-Type'])) {
                        $headers['Content-Type'] = $route['Content-Type'];
                    } else {
                        $headers = ['Content-Type' => $parsedInfo->contentType];
                    }
                    unset($routes);

                    return template($route['template'], $data, $headers);
                }
            }
            unset($routes);
        }

        return null;
    }

    /**
     * 解析URL.
     *
     * @param UrlParsedInfo $parsedInfo
     *
     * @return string
     */
    public static function parseURL($parsedInfo) {
        $pageSet = false;
        if (preg_match('#.+?\.$#', $parsedInfo->url)) {
            return false;
        }
        if (rqset('_cpn')) {
            $parsedInfo->page = irqst('_cpn');
            $pageSet          = true;
        }
        if (preg_match('#(.+?)(_([\d]+|all))?$#', $parsedInfo->name, $ms)) {
            $parsedInfo->name   = $ms[1];
            $parsedInfo->ogname = urlencode($ms[1]);
            if (isset($ms[3])) {
                if ($ms[3] === '1') {
                    return false;
                }
                if ($ms[3] == 'all') {
                    $ms[3] = PHP_INT_MAX;
                } else {
                    $ms[3] = intval($ms[3]);
                }
                if (!$pageSet) {
                    $parsedInfo->page = $ms[3];
                }
            }
        }
        if (!preg_match('#.+?(\..+)$#', $parsedInfo->url)) {
            if ($parsedInfo->page > 1 && !$pageSet) {
                return false;
            }
            $parsedInfo->name   .= '/index';
            $parsedInfo->ogname .= '/index';
            if (!$parsedInfo->ext) {
                $parsedInfo->ext = 'html';
            }
        }
        if (!$parsedInfo->ext) {
            return false;
        }
        $parsedInfo->contentType = Router::mimeContentType('a.' . $parsedInfo->ext);

        $parsedInfo->parsedUrl = ltrim(implode('', [
            $parsedInfo->path,
            '/',
            $parsedInfo->name,
            '.' . $parsedInfo->ext
        ]), '/');

        return $parsedInfo->parsedUrl;
    }
}