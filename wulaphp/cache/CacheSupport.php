<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\cache;

use wulaphp\mvc\view\View;
use wulaphp\router\Router;

/**
 * 缓存特性,与控制器配合使用。
 *
 * @package wulaphp\cache
 * @property-read \wulaphp\util\Annotation $methodAnn
 */
trait CacheSupport {
    protected final function afterRunInCacheSupport(string $action, View $view, $method) {
        $annotation = $this->methodAnn;
        if ($annotation->has('expire')) {
            $expire = $annotation->getInt('expire');
            if ($expire > 0) {
                Router::checkCache();
                $view->expire($expire);
            } else {
                $view->expire();
            }
        }

        return $view;
    }
}