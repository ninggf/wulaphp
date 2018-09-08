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

class DefaultModuleDispatcher implements IURLDispatcher {
	private $dd = null;
	private $defaultModule;

	public function __construct(IURLDispatcher $dispatcher,  $defaultModule) {
		$this->dd            = $dispatcher;
		$this->defaultModule = App::id2dir($defaultModule);
	}

	public function dispatch($url, $router, $parsedInfo) {
		if ($url == 'index.html') {
			$url = '';
		}

		return $this->dd->dispatch(untrailingslashit($this->defaultModule . '/' . $url), $router, $parsedInfo);
	}
}