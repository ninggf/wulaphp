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

use wulaphp\mvc\view\StaticFileView;

/**
 * 分发模块的静态资源.
 *
 * @package wulaphp\router
 * @internal
 */
class ModuleResDispatcher implements IURLDispatcher {
    public function dispatch(string $url, Router $router, UrlParsedInfo $parsedInfo) {
        $chunk = explode('/', $url);
        if (in_array($chunk[0], apply_filter('allowed_res_dirs', [
                MODULE_DIR,
                THEME_DIR
            ])) && preg_match('#\.(png|jpe?g|gif|css|js|eot|ttf|woff2|svg|json|html?)$#i', $url)) {
            $f = APPROOT . $url;
            if (is_file($f) && is_readable($f)) {
                return new StaticFileView($f);
            }
        }
        unset($chunk);

        return null;
    }
}