<?php

namespace wulaphp\io;

use wulaphp\app\App;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\View;

/**
 *
 * @author  Windywany
 * @package io
 * @date    12-9-16 下午5:53
 *          $Id$
 */
class Response {

	private $before_out = false;

	private $content = '';

	private $view = null;

	private static $INSTANCE = null;

	/**
	 * 初始化.
	 */
	public function __construct() {
		if (self::$INSTANCE == null) {
			if (!@ini_get('zlib.output_compression') && @ob_get_status()) {
				$this->before_out = @ob_get_contents();
				@ob_end_clean();
			}
			@ob_start(array($this, 'ob_out_handler'));
			if (defined('GZIP_ENABLED') && GZIP_ENABLED && extension_loaded('zlib')) {
				$gzip = @ini_get('zlib.output_compression');
				if (!$gzip) {
					@ini_set('zlib.output_compression', 1);
				}
				@ini_set('zlib.output_compression_level', 9);
			} else {
				@ini_set('zlib.output_compression', 0);
				@ini_set('zlib.output_compression_level', -1);
			}
		}
		self::$INSTANCE = $this;
	}

	/**
	 * 得到全局唯一Response实例.
	 *
	 * @return Response
	 */
	public static function getInstance() {
		if (self::$INSTANCE == null) {
			new Response ();
		}

		return self::$INSTANCE;
	}

	/**
	 * set response view instance.
	 *
	 * @param View $view
	 */
	public function setView($view) {
		if ($view instanceof View) {
			$this->view = $view;
		}
	}

	/**
	 * 禁用浏览器缓存.
	 */
	public static function nocache() {
		$headers = array('Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT', 'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT', 'Cache-Control' => 'no-cache, must-revalidate', 'Pragma' => 'no-cache');
		foreach ($headers as $header => $val) {
			@header($header . ': ' . $val);
		}
	}

	/**
	 * @param null $last_modify
	 * @param int  $expire
	 * @param null $etag
	 */
	function out_cache_header($last_modify = null, $expire = 3600, $etag = null) {
		$last_modify = $last_modify == null ? time() : $last_modify;
		@header('Pragma: cache');
		@header('Cache-Control: public, must-revalidate,  max-age=' . $expire, true);
		@header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modify) . ' GMT', true);
		@header('Expires: ' . gmdate('D, d M Y H:i:s', $last_modify + $expire) . ' GMT', true);
		if ($etag) {
			@header('Etag: ' . $etag);
		}
	}

	public static function cache($expire = 3600) {
		$time    = time();
		$date    = gmdate('D, d M Y H:i:s', $time) . ' GMT';
		$headers = ['Age' => $expire, 'Date' => $date, 'Expires' => gmdate('D, d M Y H:i:s', $time + $expire) . ' GMT', 'Last-Modified' => $date, 'Cache-Control' => 'public, must-revalidate, max-age=' . $expire, 'Pragma' => 'cache'];

		foreach ($headers as $header => $val) {
			@header($header . ': ' . $val);
		}
	}

	/**
	 * 跳转.
	 *
	 * @param string       $location 要转到的网址
	 * @param string|array $args     参数
	 * @param int          $status   响应代码
	 */
	public static function redirect($location, $args = "", $status = 302) {
		global $is_IIS;
		if (!$location) {
			return;
		}
		if (!empty ($args) && is_array($args)) {
			$_args = array();
			foreach ($args as $n => $v) {
				$_args [ $n ] = $n . '=' . urlencode($v);
			}
			$args = implode('&', $_args);
		}
		if (!empty ($args) && is_string($args)) {
			if (strpos($location, '?') !== false) {
				$location .= '&' . $args;
			} else {
				$location .= '?' . $args;
			}
		}
		if (isset ($_SERVER ["HTTP_X_AJAX_TYPE"])) {
			@header('X-AJAX-REDIRECT:' . $location);
		} else {
			if ($is_IIS) {
				@header("Refresh: 0;url=$location");
			} else {
				if (php_sapi_name() != 'cgi-fcgi') {
					status_header($status); // This causes problems on IIS and some
				}
				@header("Location: $location", true, $status);
			}
		}
		exit ();
	}

	/**
	 * 响应对应的状态码.
	 *
	 * @param int          $status respond status code.
	 * @param string|array $message
	 */
	public static function respond($status = 404, $message = '') {
		status_header($status);
		if ($status == 404) {
			$data ['message'] = $message;
			$view             = template('404.tpl', $data);
			echo $view->render();
		} else if ($message) {
			if (is_array($message)) {
				$message = json_encode($message);
			}
			echo $message;
		}
		exit ();
	}

	/**
	 * 设置cookie.
	 *
	 * @param string      $name 变量名
	 * @param null|mixed  $value
	 * @param null|int    $expire
	 * @param null|string $path
	 * @param null|string $domain
	 * @param null|bool   $security
	 */
	public static function cookie($name, $value = null, $expire = null, $path = null, $domain = null, $security = null) {
		$settings       = App::cfg();
		$cookie_setting = array_merge2(array('expire' => 0, 'path' => '/', 'domain' => '', 'security' => false), $settings->get('cookie', array()));
		if ($expire == null) {
			$expire = intval($cookie_setting ['expire']);
		}
		if ($path == null) {
			$path = $cookie_setting ['path'];
		}
		if ($domain == null) {
			$domain = $cookie_setting ['domain'];
		}
		if ($security == null) {
			$security = $cookie_setting ['security'];
		}
		if ($expire != 0) {
			$expire = time() + $expire;
		}
		@setcookie($name, $value, $expire, $path, $domain, $security);
	}

	/**
	 * 输出view产品的内容.
	 *
	 * @param View $view
	 * @param bool $return
	 *
	 * @filter before_output_content $content
	 * @return string
	 */
	public function output($view = null, $return = false) {
		if ($view instanceof View) {
			$this->view = $view;
		} else if (is_string($view) || is_bool($view) || is_numeric($view)) {
			$this->view = new SimpleView ($view);
		} else if (is_array($view)) {
			$this->view = new JsonView ($view);
		}
		if ($this->view instanceof View) {
			if (!$return) {
				$this->view->echoHeader();
			}
			$content = $this->view->render();
			if ($return) {
				return $content;
			} else {
				$content = apply_filter('before_output_content', $content);
				echo str_replace('<!-- benchmark -->', (microtime(true) - WULA_STARTTIME), $content);
			}
		} else {
			Response::respond(404);
		}
	}

	/**
	 * 此方法不应该直接调用，用于ob_start处理output buffer中的内容。
	 *
	 * @param string $content
	 *
	 * @filter filter_output_conten $content
	 * @return string
	 */
	public function ob_out_handler($content) {
		$this->content = apply_filter('filter_output_conten', $content);
		if ($this->before_out && DEBUG == DEBUG_DEBUG) {
			log_warn($this->before_out);
		}

		return $this->content;
	}

	/**
	 * 关闭响应，将内容输出的浏览器，同时触发after_content_output勾子.
	 *
	 * @param bool $exit
	 *
	 * @fire after_content_output $content
	 */
	public function close($exit = true) {
		if ($exit) {
			exit ();
		} else {
			fire('after_content_output', $this->content);
		}
	}
}

// END OF FILE response.php