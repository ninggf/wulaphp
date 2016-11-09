<?php
namespace wulaphp\app;

use wulaphp\cache\RtCache;

class ModuleLoader {
	/**
	 * 加载启动文件bootstrap.php注册模块.
	 */
	public function load() {
		global $_wula_namespace_classpath;
		$_wula_namespace_classpath['tmpmodules'] = MODULES_PATH;
		$modules                                 = $this->scanModules();
		foreach ($modules as $file) {
			@include $file;
		}
		unset($_wula_namespace_classpath['tmpmodules']);
	}

	/**
	 * 扫描模块目录,返回目录名与引导文件数组
	 * @return array
	 */
	public function scanModules() {
		$modules = RtCache::get('loader@modules');
		if (!$modules) {
			$it      = new \DirectoryIterator (MODULE_ROOT);
			$modules = [];
			foreach ($it as $dir) {
				if ($dir->isDot()) {
					continue;
				}
				if ($dir->isDir()) {
					$dirname = $dir->getFilename();
					$boot    = MODULE_ROOT . $dirname . '/bootstrap.php';
					if (is_file($boot)) {
						$modules[ $dirname ] = $boot;
					}
				}
			}
			RtCache::add('loader@modules', $modules);
		}

		return $modules;
	}

	/**
	 * 加载模块下的类.
	 *
	 * @param string $file
	 *
	 * @return string|null
	 */
	public function loadClass($file) {
		$clf = MODULE_ROOT . $file;
		if (is_file($clf)) {
			return $clf;
		}

		return null;
	}

	/**
	 * 模块是否启用.
	 *
	 * @param \wulaphp\app\Module $module
	 *
	 * @return bool
	 */
	public function isEnabled(Module $module) {
		return true;
	}
}