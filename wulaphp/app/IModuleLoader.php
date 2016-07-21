<?php
namespace wulaphp\app;

/**
 * 模块加载器接口，定义加载模块方法与加载模块中类的方法.
 *
 * @author leo
 *
 */
interface IModuleLoader {

    /**
     * 加载模块.
     */
    function load();

    /**
     * 加载类.
     *
     * @param string $module 模块.
     * @param string $file 相对于MODULE_ROOT的路径文件路径.
     * @return mixed 加载成功返回类文件全路径,反之返回null.
     */
    function loadClass($module, $file);
}
