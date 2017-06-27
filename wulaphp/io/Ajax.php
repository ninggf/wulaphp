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
 *      style:'',
 *      target:'',
 *      args: {}
 *  }
 *
 *  code 与 action 是肯定有的，message,target,args根据不同的action而不同,style定义message的显示样式.
 *  message 可以有两种格式：
 *  1. 字符串
 *  2. 对象
 *     {
 *          "title":"消息提示标题",
 *          "message":"消息正文"
 *      }
 *
 * @author  Leo Ning <windywany@gmail.com>
 * @package wulaphp\io
 */
class Ajax {
	const           SUCCESS      = 200;
	const           ERROR        = 500;
	const           WARNING      = 400;
	const           INFO         = 300;
	const           STYLE_NOTICE = 'notice';
	const           STYLE_ALERT  = 'alert';
	const           ACT_UPDATE   = 'update';//更新元素
	const           ACT_DIALOG   = 'dialog';//弹出对话框
	const           ACT_RELOAD   = 'reload';//重新加载
	const           ACT_CLICK    = 'click';//点击元素
	const           ACT_REDIRECT = 'redirect';//重定向
	const           ACT_VALIDATE = 'validate';//表单验证
	const           ACT_SCRIPT   = 'script';//JS

	/**
	 * 成功.
	 *
	 * @param string|array $message 提示消息.
	 * @param int          $style   显示样式.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function success($message, $style = null) {
		return new JsonView(['code' => self::SUCCESS, 'message' => $message, 'style' => $style], ['ajax' => '1']);
	}

	/**
	 * 失败.
	 *
	 * @param string|array $message
	 * @param int          $style 显示样式.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function error($message, $style = null) {
		return new JsonView(['code' => self::ERROR, 'message' => $message, 'style' => $style], ['ajax' => '1']);
	}

	/**
	 * 警告.
	 *
	 * @param string|array $message
	 * @param int          $style 显示样式.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function warn($message, $style = null) {
		return new JsonView(['code' => self::WARNING, 'message' => $message, 'style' => $style], ['ajax' => '1']);
	}

	/**
	 * 提示.
	 *
	 * @param string|array $message
	 * @param string       $style 显示样式.
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function info($message, $style = null) {
		return new JsonView(['code' => self::INFO, 'message' => $message, 'style' => $style], ['ajax' => '1']);
	}

	/**
	 * 更新元素内容.
	 *
	 * @param string       $target  css selector
	 * @param string       $content html
	 * @param string|array $message 提示信息.
	 * @param bool         $append  是否是追加内容.
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function update($target, $content, $message = '', $append = false, $style = null) {
		$args = ['content' => $content, 'append' => $append];

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'style' => $style, 'args' => $args, 'action' => self::ACT_UPDATE], ['ajax' => '1']);
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

		return new JsonView(['code' => self::SUCCESS, 'args' => $args, 'action' => self::ACT_DIALOG], ['ajax' => '1']);
	}

	/**
	 * 重新加载.
	 *
	 * @param string       $target  css selector,此元素（集合）的data('loadObject')需要返回一个拥有reload方法的对象.
	 * @param string|array $message message
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function reload($target, $message = '', $style = null) {

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'style' => $style, 'action' => self::ACT_RELOAD], ['ajax' => '1']);
	}

	/**
	 * 点击元素.
	 *
	 * @param string       $target  css selector
	 * @param string|array $message message
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function click($target, $message = '', $style = null) {

		return new JsonView(['code' => self::SUCCESS, 'target' => $target, 'message' => $message, 'style' => $style, 'action' => self::ACT_CLICK], ['ajax' => '1']);
	}

	/**
	 * 重定向.
	 *
	 * @param string       $url
	 * @param string|array $message
	 * @param string       $style
	 * @param bool         $hash
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function redirect($url, $message = '', $style = null, $hash = false) {

		return new JsonView(['code' => self::SUCCESS, 'target' => $url, 'hash' => $hash, 'message' => $message, 'style' => $style, 'action' => self::ACT_REDIRECT], ['ajax' => '1']);
	}

	/**
	 * 表单验证出错.
	 *
	 * @param array        $errors  错误信息.
	 * @param string|array $message 提示信息.
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function validate($errors, $message = '', $style = null) {
		$args = ['errors' => $errors];

		return new JsonView(['code' => self::ERROR, 'message' => $message, 'style' => $style, 'args' => $args, 'action' => self::ACT_VALIDATE], ['ajax' => '1'], 422);
	}

	/**
	 * 直接运行$script.
	 *
	 * @param string       $script
	 * @param string|array $message
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function script($script, $message = '', $style = null) {
		return new JsonView(['code' => self::SUCCESS, 'target' => $script, 'message' => $message, 'style' => $style, 'action' => self::ACT_SCRIPT], ['ajax' => '1']);
	}
}