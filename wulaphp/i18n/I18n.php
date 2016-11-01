<?php

namespace wulaphp\i18n {

	class I18n {
		private static $languages = [];

		public static function addLang($dir) {
			$lang = defined('LANGUAGE') ? LANGUAGE : 'en';
			if (is_dir($dir . '/' . $lang)) {
				$it = new \DirectoryIterator ($dir . '/' . $lang);
				foreach ($it as $f) {
					if ($f->isDot()) {
						continue;
					}
					if ($f->isFile()) {
						$fname    = $f->getFilename();
						$lf       = $dir . '/' . $lang . '/' . $fname;
						$language = @include $lf;
						if (is_array($language)) {
							self::$languages = array_merge(self::$languages, $language);
						}
					}
				}
			}
		}

		public static function translate($text, $domain, $args) {
			if (isset(self::$languages[ $domain ][ $text ])) {
				return vsprintf(self::$languages[ $domain ][ $text ], $args);
			}

			return '';
		}
	}
}
namespace {

	use wulaphp\i18n\I18n;

	function __($text, ...$args) {
		$text   = explode('@', $text);
		$str    = $text[0];
		$domain = isset($text[1]) ? $text[1] : 'core';

		return I18n::translate($str, $domain, $args);
	}

	function ___($text) {

	}
}