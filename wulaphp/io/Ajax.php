<?php

namespace wulaphp\io;

use wulaphp\mvc\view\JsonView;

/**
 * Ajax Response with wula standard.
 *
 * the json is:
 *  {
 *      code:200,
 *      action:'',
 *      message: '',
 *      target:'',
 *      args: {}
 *  }
 *
 *  code 与 action 是肯定有的，message,target,args根据不同的action而不同.
 *
 * @author  Leo Ning <windywany@gmail.com>
 * @package wulaphp\io
 */
class Ajax {
	const         SUCCESS      = 200;
	const         ERROR        = 300;
	const         WARNING      = 400;
	const         INFO         = 500;
	const         ACT_TIP      = 'tip';//提示信息
	const         ACT_CALLBACK = 'callback';//回调js函数.
	const         ACT_DIALOG   = 'dialog';//弹出对话框
	const         ACT_UPDATE   = 'update';//更新元素
	const         ACT_RELOAD   = 'reload';//重新加载
	const         ACT_CLICK    = 'click';//点击元素
	const         ACT_REDIRECT = 'redirect';//重定向
	const         ACT_VALIDATE = 'validate';//表单验证
	const         ACT_SCRIPT   = 'script';//JS

	/**
	 * 成功.
	 *
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function success($message) {
		return new JsonView(['code' => self::SUCCESS, 'message' => $message, 'action' => self::ACT_TIP]);
	}

	/**
	 * 失败.
	 *
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function error($message) {
		return new JsonView(['code' => self::ERROR, 'message' => $message, 'action' => self::ACT_TIP]);
	}

	/**
	 * 警告.
	 *
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function warn($message) {
		return new JsonView(['code' => self::WARNING, 'message' => $message, 'action' => self::ACT_TIP]);
	}

	/**
	 * 提示.
	 *
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function info($message) {
		return new JsonView(['code' => self::INFO, 'message' => $message, 'action' => self::ACT_TIP]);
	}

	/**
	 * 调用js函数.
	 *
	 * @param string $func    要调用的js函数
	 * @param array  $args    参数
	 * @param string $message 提示信息
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function callback($func, $args = [], $message = '') {
		return new JsonView(['code' => self::SUCCESS, 'message' => $message, 'target' => $func, 'args' => $args, 'action' => self::ACT_CALLBACK]);
	}

	/**
	 * 弹出对话框.
	 *
	 * @param string $content 内容HTML或内容的URL.
	 * @param string $title   对话框标题.
	 * @param int    $width   宽度,0 为auto.
	 * @param int    $height  高度,0 为auto.
	 * @param bool   $ajax
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function dialog($content, $title, $width = 0, $height = 0, $ajax = false) {
		$args = ['title' => $title, 'width' => $width, 'height' => $height, 'ajax' => $ajax, 'content' => $content];

		return new JsonView(['code' => self::SUCCESS, 'args' => $args, 'action' => self::ACT_DIALOG]);
	}

	/**
	 * 更新元素内容.
	 *
	 * @param string $target  css selector
	 * @param string $content html
	 * @param string $message 提示信息.
	 * @param bool   $append  是否是追加内容.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function update($target, $content, $message = '', $append = false) {
		$args = ['content' => $content, 'append' => $append];

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'args' => $args, 'action' => self::ACT_UPDATE]);
	}

	/**
	 * 重新加载.
	 *
	 * @param string $target  css selector
	 * @param string $message message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function reload($target, $message = '') {

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'action' => self::ACT_RELOAD]);
	}

	/**
	 * 点击元素.
	 *
	 * @param string $target  css selector
	 * @param string $message message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function click($target, $message = '') {

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'action' => self::ACT_CLICK]);
	}

	/**
	 * 重定向.
	 *
	 * @param string $url
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function redirect($url, $message = '') {

		return new JsonView(['code' => self::SUCCESS, 'target' => $url, 'message' => $message, 'action' => self::ACT_REDIRECT]);
	}

	/**
	 * 表单验证出错.
	 *
	 * @param string $target  表单.
	 * @param array  $errors  错误信息.
	 * @param string $message 提示信息.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function validate($target, $errors, $message = '') {
		$args = ['errors' => $errors];

		return new JsonView(['code' => self::WARNING, 'target' => $target, 'message' => $message, 'args' => $args, 'action' => self::ACT_VALIDATE]);
	}

	/**
	 * 直接运行$script.
	 *
	 * @param string $script
	 * @param string $message
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function script($script, $message = '') {
		return new JsonView(['code' => self::SUCCESS, 'target' => $script, 'message' => $message, 'action' => self::ACT_SCRIPT]);
	}
}