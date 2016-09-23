<?php
namespace wulaphp\mvc\view;

interface Renderable {

	/**
	 * 将自身绘制成html片断.
	 *
	 * @return string html fragment.
	 */
	function render();
}
