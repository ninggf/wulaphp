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
	public static function success($message = '', $style = null) {
		if (is_array($message)) {
			$msg     = unget($message, 'message');
			$args    = $message;
			$message = $msg ? $msg : '';
		} else {
			$args = [];
		}

		return new JsonView([
			'code'    => self::SUCCESS,
			'message' => $message,
			'args'    => $args,
			'style'   => $style
		], ['ajax' => '1']);
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
		if (is_array($message)) {
			$msg     = unget($message, 'message');
			$args    = $message;
			$message = $msg ? $msg : '';
		} else {
			$args = [];
		}

		return new JsonView([
			'code'    => self::ERROR,
			'message' => $message,
			'args'    => $args,
			'style'   => $style
		], ['ajax' => '1']);
	}

	public static function fatal($message, $code = 500) {
		if (is_array($message)) {
			$msg     = unget($message, 'message');
			$args    = $message;
			$message = $msg ? $msg : '';
		} else {
			$args = [];
		}

		return new JsonView([
			'code'    => self::ERROR,
			'message' => $message,
			'args'    => $args,
			'style'   => 'notice'
		], ['ajax' => '1'], $code);
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
		if (is_array($message)) {
			$msg     = unget($message, 'message');
			$args    = $message;
			$message = $msg ? $msg : '';
		} else {
			$args = [];
		}

		return new JsonView([
			'code'    => self::WARNING,
			'message' => $message,
			'args'    => $args,
			'style'   => $style
		], ['ajax' => '1']);
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
		if (is_array($message)) {
			$msg     = unget($message, 'message');
			$args    = $message;
			$message = $msg ? $msg : '';
		} else {
			$args = [];
		}

		return new JsonView([
			'code'    => self::INFO,
			'message' => $message,
			'args'    => $args,
			'style'   => $style
		], ['ajax' => '1']);
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

		return new JsonView([
			'code'    => self::SUCCESS,
			'target'  => $target,
			'message' => $message,
			'style'   => $style,
			'args'    => $args,
			'action'  => self::ACT_UPDATE
		], ['ajax' => '1']);
	}

	/**
	 * 弹出对话框.
	 *
	 * @param string|array $content 内容HTML或内容的URL.
	 * @param string       $title   对话框标题.
	 * @param string       $width   宽度
	 * @param string       $height  高度
	 * @param bool         $ajax    ajax方式打开
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function dialog($content, $title = '', $width = 'auto', $height = 'auto', $ajax = true) {
		if (is_array($content)) {
			$args          = $content;
			$args['title'] = $title;
			$args['area']  = $width . ',' . $height;
			$args['type']  = $ajax ? 'ajax' : 2;
		} else {
			$args = [
				'title'   => $title,
				'area'    => $width . ',' . $height,
				'type'    => $ajax ? 'ajax' : 2,
				'content' => $content
			];
		}

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

		return new JsonView([
			'code'    => self::SUCCESS,
			'target'  => $target,
			'message' => $message,
			'style'   => $style,
			'action'  => self::ACT_RELOAD
		], ['ajax' => '1']);
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

		return new JsonView([
			'code'    => self::SUCCESS,
			'target'  => $target,
			'message' => $message,
			'style'   => $style,
			'action'  => self::ACT_CLICK
		], ['ajax' => '1']);
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

		return new JsonView([
			'code'    => self::SUCCESS,
			'target'  => $url,
			'hash'    => $hash,
			'message' => $message,
			'style'   => $style,
			'action'  => self::ACT_REDIRECT
		], ['ajax' => '1']);
	}

	/**
	 * 表单验证出错.
	 *
	 * @param string       $form    表单ID
	 * @param array        $errors  错误信息.
	 * @param string|array $message 提示信息.
	 * @param string       $style
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 */
	public static function validate($form, $errors, $message = '', $style = null) {
		$args = $errors;

		return new JsonView([
			'code'    => self::ERROR,
			'target'  => $form,
			'message' => $message,
			'style'   => $style,
			'args'    => $args,
			'action'  => self::ACT_VALIDATE
		], ['ajax' => '1'], 422);
	}
}