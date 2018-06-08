<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\view;

class JsView extends View {
	/**
	 *
	 * @param string $data
	 * @param array  $headers
	 * @param int    $status
	 */
	public function __construct($data, $headers = [], $status = 200) {
		parent::__construct('', '', $headers, $status);
		$this->data = $data;
	}

	/**
	 * 绘制
	 *
	 * @return string
	 */
	public function render() {
		return $this->data;
	}

	protected function setHeader() {
		$this->headers['Content-type'] = 'application/javascript';
	}
}