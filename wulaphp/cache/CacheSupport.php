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
use wulaphp\util\Annotation;

trait CacheSupport {
	public function afterRunInCacheSupport($action, View $view, $method) {
		if (APP_MODE == 'pro') {
			$annotation = new Annotation($method);
			if ($annotation->has('expire')) {
				$expire = $annotation->getInt('expire');
				if ($expire > 0) {
					$view->expire($expire);
				} else {
					$view->expire();
				}
			}
		}

		return $view;
	}
}