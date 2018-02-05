<?php

namespace wulaphp\mvc\view;

class HtmlView extends View {

	/**
	 * 绘制
	 *
	 * @return string
	 * @throws
	 */
	public function render() {
		$this->tpl = MODULES_PATH . $this->tpl;
		if (is_file($this->tpl)) {
			extract($this->data);
			@ob_start();
			@include $this->tpl;
			$content = @ob_get_contents();
			@ob_end_clean();

			return $content;
		} else {
			throw_exception('tpl is not found:' . $this->tpl);
		}
	}

	public function setHeader() {
		$this->headers['Content-Type'] = 'text/html; charset=utf-8';
	}
}