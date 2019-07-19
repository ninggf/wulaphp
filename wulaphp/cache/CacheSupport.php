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
use wulaphp\util\Annotation;

trait CacheSupport {
    protected function afterRunInCacheSupport($action, View $view, $method) {
        $annotation = new Annotation($method);
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