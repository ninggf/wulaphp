<?php

namespace wulaphp\app;

use wulaphp\cache\RtCache;

/**
 * 默认的扩展加载器。
 *
 * @package wulaphp\app
 */
class ExtensionLoader {
	public function load() {
		$extensions = $this->scanExtensions();
		foreach ($extensions as $file) {
			include $file;
		}
	}

	/**
	 * 扫描模块目录,返回目录名与引导文件数组
	 * @return array
	 */
	public function scanExtensions() {
		$extensions = RtCache::get('loader@extensions');
		if (is_array($extensions)) {
			return $extensions;
		}
		if (is_dir(EXTENSIONS_PATH)) {
			$it         = new \DirectoryIterator (EXTENSIONS_PATH);
			$extensions = [];
			foreach ($it as $dir) {
				if ($dir->isDot()) {
					continue;
				}
				if ($dir->isDir()) {
					$dirname = $dir->getFilename();
					$boot    = EXTENSIONS_PATH . $dirname . DS . $dirname . '.php';
					if (is_file($boot)) {
						$extensions[ $dirname ] = $boot;
					}
					$this->scanSubDir($dir->getRealPath(), $extensions, $dirname);
				}
			}
			RtCache::add('loader@extensions', $extensions);
		} else {
			$extensions = [];
		}

		return $extensions;
	}

	protected function scanSubDir($subdir, &$extensions, $parent) {
		$it = new \DirectoryIterator ($subdir);
		foreach ($it as $dir) {
			if ($dir->isDot()) {
				continue;
			}
			if ($dir->isDir()) {
				$dirname = $dir->getFilename();
				$boot    = $subdir . DS . $dirname . DS . $dirname . '.php';
				if (is_file($boot)) {
					$extensions[ $parent . '/' . $dirname ] = $boot;
				}
			}
		}
	}
}