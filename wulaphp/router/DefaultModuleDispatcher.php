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

/**
 * Class DefaultModuleDispatcher
 * @package wulaphp\router
 * @internal
 */
class DefaultModuleDispatcher implements IURLDispatcher {
    private $dd = null;
    private $defaultModule;

    public function __construct(IURLDispatcher $dispatcher, string $defaultModule) {
        $this->dd            = $dispatcher;
        $this->defaultModule = $defaultModule;
    }

    public function dispatch(string $url, Router $router, UrlParsedInfo $parsedInfo) {
        if ($url == 'index.html') {
            $url = '';
        }
        if ($url) {
            $urls = explode('/', $url);
            $id   = App::dir2id($urls[0], true);
            if ($id) {
                // 已有模块的URL不分发给默认模块
                return null;
            }
        }

        return $this->dd->dispatch(untrailingslashit($this->defaultModule . '/' . $url), $router, $parsedInfo);
    }
}