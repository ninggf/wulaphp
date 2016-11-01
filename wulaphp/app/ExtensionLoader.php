<?php

namespace wulaphp\app;

use wulaphp\cache\RtCache;

class ExtensionLoader {
	public function load() {
		$extensions = $this->scanModules();
		foreach ($extensions as $file) {
			@include $file;
		}
	}

	/**
	 * 扫描模块目录,返回目录名与引导文件数组
	 * @return array
	 */
	public function scanModules() {
		$extensions = RtCache::get('loader@extensions');
		if (!$extensions && is_dir(EXTENSIONS_PATH)) {
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
				}
			}
			RtCache::add('loader@extensions', $extensions);
		} else {
			$extensions = [];
		}

		return $extensions;
	}
}