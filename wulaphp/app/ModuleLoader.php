<?php

namespace wulaphp\app;

use wulaphp\cache\RtCache;

/**
 * 默认模块加载器，加载`modules`目录里的所有模块。
 *
 * @package wulaphp\app
 */
class ModuleLoader {
    /**
     * 加载启动文件bootstrap.php注册模块.
     */
    public function load() {
        global $_wula_namespace_classpath;
        $_wula_namespace_classpath['tmpmodules'] = MODULES_PATH;
        $modules                                 = $this->scanModules();
        foreach ($modules as $m => $file) {
            try {
                if ($file === true) {
                    App::register(new DefaultModule($m));
                } else {
                    include $file;
                }
            } catch (\Exception $e) {
                log_warn($e->getMessage(), 'loader');
            }
        }
        unset($_wula_namespace_classpath['tmpmodules']);
    }

    /**
     * 扫描模块目录,返回目录名与引导文件数组
     *
     * @return array
     */
    public function scanModules() {
        $modules = RtCache::get('loader@modules');
        if (!$modules) {
            $modules = [];
            if (is_dir(MODULE_ROOT)) {
                $it = new \DirectoryIterator (MODULE_ROOT);
                foreach ($it as $dir) {
                    if ($dir->isDot()) {
                        continue;
                    }
                    if ($dir->isDir()) {
                        $dirname = $dir->getFilename();
                        $boot    = MODULE_ROOT . $dirname . '/bootstrap.php';
                        if (is_file($boot)) {
                            $modules[ $dirname ] = $boot;
                        } else {
                            $modules[ $dirname ] = true;
                        }
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
     * @param string $file 类文件名
     *
     * @return string|null 类文件路径。
     */
    public function loadClass(string $file): ?string {
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
     * @return bool 启用返回true,反之返回false
     */
    public function isEnabled(Module $module): bool {
        if ($module) {
            $module->installed = true;

            return true;
        }

        return false;
    }
}