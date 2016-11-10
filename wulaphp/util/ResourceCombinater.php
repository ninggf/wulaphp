<?php

namespace wulaphp\util;

class ResourceCombinater {

	public static function combinateJS($files, $destFile) {
		if (self::needUpdate($files, $destFile)) {
			$contents = [];
			foreach ($files as $file) {
				$mined = preg_match('#.*\.min\.js$#i', $file);
				$cnt   = @file_get_contents($file);
				if ($cnt) {
					if (!$mined) {
						$cnt = \JSMin::minify($cnt);
					}
					$contents[] = $cnt;
				}
			}

			$rst = @file_put_contents($destFile, implode(';', $contents)) > 0;
			unset($contents);

			return $rst;
		}

		return true;
	}

	public static function combinateCSS($files, $destFile) {
		if (self::needUpdate($files, $destFile)) {
			$contents = [];
			$cm       = new \CSSmin ();
			foreach ($files as $file) {
				$mined = preg_match('#.*\.min\.css$#i', $file);
				$cnt   = @file_get_contents($file);
				if ($cnt) {
					$dir = WWWROOT_DIR . rtrim(substr(dirname($file), strlen(WWWROOT)), '/');
					$cnt = preg_replace('#url\s*\((?![\s\'"]*data:)[\'"]?(.+?)[\'"]?\s*\)#ims', 'url(' . $dir . '/\1)', $cnt);
					if (!$mined) {
						$cnt = $cm->run($cnt);
					}
					$contents[] = $cnt;
				}
			}
			$rst = @file_put_contents($destFile, implode('', $contents)) > 0;
			unset($contents);

			return $rst;
		}

		return true;
	}

	private static function needUpdate($files, $destFile) {
		if (is_file($destFile)) {
			$mtime = filemtime($destFile);
			foreach ($files as $f) {
				if (is_file($f) && filemtime($f) > $mtime) {
					return true;
				}
			}
		} else {
			return true;
		}

		return false;
	}
}