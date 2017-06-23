<?php

namespace wulaphp\i18n {
	/**
	 * 多语言支持类.
	 *
	 * @package wulaphp\i18n
	 */
	class I18n {
		private static $languages = [];

		/**
		 * 添加语言目录.
		 *
		 * @param string $dir
		 */
		public static function addLang($dir) {
			$lang = defined('LANGUAGE') ? LANGUAGE : 'en';
			$lf   = $dir . DS . $lang . '.php';
			if (!is_file($lf) && strpos($lang, '-', 1)) {
				$lf = $dir . DS . substr($lang, 0, 2) . '.php';
			}
			if (is_file($lf)) {
				$language = @include $lf;
				if (is_array($language)) {
					self::$languages = array_merge_recursive(self::$languages, $language);
				}
			}
		}

		/**
		 * 基于域进行翻译.
		 *
		 * @param string $text
		 * @param array  $args
		 * @param string $domain default is 'core'.
		 *
		 * @return string
		 */
		public static function translate1($text, $args, $domain = '') {
			if (isset(self::$languages[ $domain ][ $text ])) {
				return @vsprintf(self::$languages[ $domain ][ $text ], $args);
			}

			return @vsprintf($text, $args);
		}

		/**
		 * 翻译字符.
		 *
		 * @param $text
		 * @param $args
		 *
		 * @return string
		 */
		public static function translate($text, $args) {
			if (isset(self::$languages[ $text ])) {
				return @vsprintf(self::$languages[ $text ], $args);
			}

			return @vsprintf($text, $args);
		}
	}
}

namespace {

	use wulaphp\i18n\I18n;

	/**
	 * 翻译.
	 *
	 * @param string $text 要翻译的字符串.
	 * @param array  $args 参数.
	 *
	 * @return string
	 */
	function __($text, ...$args) {
		return I18n::translate($text, $args);
	}

	/**
	 * 基于域进行翻译.字符和域用'@'分隔,如: 'abc@dashboard'.
	 *
	 * @param string $text 要翻译的字符串.
	 * @param array  $args 参数.
	 *
	 * @return string
	 */
	function _t($text, ...$args) {
		$text   = explode('@', $text);
		$str    = $text[0];
		$domain = isset($text[1]) ? '@' . $text[1] : '';

		return I18n::translate1($str, $args, $domain);
	}

	function _i18n($file, $ext = '.js') {
		if (!$file) {
			return '';
		}
		$lang = defined('LANGUAGE') ? LANGUAGE : 'en';
		$rf   = substr($file, strlen(WWWROOT_DIR));
		$ext  = strtolower($ext);
		if (!is_file(WWWROOT . $rf . DS . $lang . $ext) && strpos($lang, '-', 1)) {
			$lang = substr($lang, 0, 2);
		}
		if (is_file(WWWROOT . $rf . DS . $lang . $ext)) {
			if ($ext == '.js') {
				return "<script type=\"text/javascript\" src=\"{$file}/{$lang}{$ext}\"></script>";
			} else if ($ext == '.css') {
				return "<link rel=\"stylesheet\" href=\"{$file}/{$lang}{$ext}\"/>";
			} else {
				return "{$file}/{$lang}{$ext}";
			}
		}

		return '';
	}

	// 翻译
	function smarty_modifiercompiler_t($params) {
		$str  = array_shift($params);
		$args = smarty_vargs($params);
		if ($args) {
			return "__({$str},$args)";
		} else {
			return "__({$str})";
		}
	}

	//带域翻译
	function smarty_modifiercompiler_tf($params) {
		$str  = array_shift($params);
		$args = smarty_vargs($params);
		if ($args) {
			return "_t($str,$args)";
		} else {
			return "_t($str)";
		}
	}

	// 加载语言相关资源使用
	function smarty_modifiercompiler_i18n($params) {
		$ext = isset($params[1]) ? $params[1] : "'.js'";

		return "_i18n({$params[0]},$ext)";
	}
}