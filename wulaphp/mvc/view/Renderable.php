<?php

namespace wulaphp\mvc\view;
/**
 * 绘制接口.
 *
 * @package wulaphp\mvc\view
 */
interface Renderable {

	/**
	 * 将自身绘制成html片断.
	 *
	 * @return string html fragment.
	 */
	public function render();
}
