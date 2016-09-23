<?php
namespace wulaphp\mvc\view;

/**
 * HTML视图
 *
 * 使用PHP 语法定义的HTML视图
 *
 * @author  Guangfeng Ning <windywany@gmail.com> 2010-11-14 12:25
 * @version 1.0
 * @since   1.0
 * @package view
 */
class SimpleView extends View {

	/**
	 *
	 * @param array|string $data
	 */
	public function __construct($data) {
		parent::__construct(array($data));
	}

	/**
	 * 绘制
	 *
	 * @return string
	 */
	public function render() {
		return array_pop($this->data);
	}

	public function setHeader() {
		@header('Content-type: text/plain; charset=utf-8', true);
	}
}