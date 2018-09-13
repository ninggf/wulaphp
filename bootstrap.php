<?php
/**
 * Project:     Wulaphp: another mvc framework of php based on php 5.6.9+
 * File:        bootstrap.php
 *
 * 此文件用于引导wulaphp framework.
 *
 * @link      https://www.wulaphp.com/
 * @author    leo <windywany@163.com>
 * @package   wulaphp
 * @version   2.0.0
 * @since     1.0.0
 */

use wulaphp\app\App;
use wulaphp\cache\RtCache;

# 项目根目录检测
if (!defined('APPROOT')) {
    if (defined('PHPUNIT_COMPOSER_INSTALL')) {
        return;
    } else {
        !trigger_error('define "APPROOT" first', E_USER_ERROR) or exit(1);
    }
}
@ob_start();
define('WULA_STARTTIME', microtime(true));
define('WULA_VERSION', '2.5.0');
define('WULA_RELEASE', 'rc');
defined('BUILD_NUMBER') or define('BUILD_NUMBER', '0');
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
if (version_compare('5.6.9', phpversion(), '>')) {
    !trigger_error(sprintf('Your php version is %s,but wulaphp required PHP 5.6.9 or higher', phpversion()), E_USER_ERROR) or exit(1);
}
/* 常用目录定义 */
define('WULA_ROOT', __DIR__ . DS);
defined('MODULE_DIR') or define('MODULE_DIR', 'modules');
defined('THEME_DIR') or define('THEME_DIR', 'themes');
defined('CONF_DIR') or define('CONF_DIR', 'conf');
defined('LIBS_DIR') or define('LIBS_DIR', 'includes');
defined('EXTENSION_DIR') or define('EXTENSION_DIR', 'extensions');
defined('WWWROOT_DIR') or define('WWWROOT_DIR', '/');
defined('PUBLIC_DIR') or define('PUBLIC_DIR', 'wwwroot');
defined('ASSETS_DIR') or define('ASSETS_DIR', 'assets');
defined('VENDOR_DIR') or define('VENDOR_DIR', 'assets');
defined('STORAGE_DIR') or define('STORAGE_DIR', 'storage');
defined('TMP_DIR') or define('TMP_DIR', 'tmp');
defined('LOGS_DIR') or define('LOGS_DIR', 'logs');
defined('WWWROOT') or define('WWWROOT', APPROOT . PUBLIC_DIR . DIRECTORY_SEPARATOR);
define('WEB_ROOT', WWWROOT);//alias of WWWROOT
define('EXTENSIONS_PATH', APPROOT . EXTENSION_DIR . DS);
define('LIBS_PATH', APPROOT . LIBS_DIR . DS);
define('TMP_PATH', APPROOT . STORAGE_DIR . DS . TMP_DIR . DS);
define('CONFIG_PATH', APPROOT . CONF_DIR . DS);
define('MODULES_PATH', APPROOT . MODULE_DIR . DS);
define('MODULE_ROOT', MODULES_PATH);
define('THEME_PATH', APPROOT . THEME_DIR . DS);
define('LOGS_PATH', APPROOT . STORAGE_DIR . DS . LOGS_DIR . DS);
defined('MODULE_LOADER_CLASS') or define('MODULE_LOADER_CLASS', 'wulaphp\app\ModuleLoader');
defined('EXTENSION_LOADER_CLASS') or define('EXTENSION_LOADER_CLASS', 'wulaphp\app\ExtensionLoader');
defined('CONFIG_LOADER_CLASS') or define('CONFIG_LOADER_CLASS', 'wulaphp\conf\ConfigurationLoader ');
define('DEBUG_OFF', 1000);
define('DEBUG_ERROR', 400);
define('DEBUG_WARN', 300);
define('DEBUG_INFO', 200);
define('DEBUG_DEBUG', 100);
define('EXIT_SUCCESS', 0);
define('EXIT_ERROR', 1);
define('EXIT_CONTINUE', 2);
define('PHP_RUNTIME_NAME', php_sapi_name());
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) {
    define('VISITING_DOMAIN', @explode(':', $_SERVER['HTTP_HOST'])[0]);
} else {
    define('VISITING_DOMAIN', '');
}
if (@ini_get('register_globals')) {
    !trigger_error('please close "register_globals" in php.ini file.') or exit(1);
}
if (defined('MAX_RUNTIME_LIMIT')) {
    set_time_limit(intval(MAX_RUNTIME_LIMIT));
}
defined('RUNTIME_MEMORY_LIMIT') or define('RUNTIME_MEMORY_LIMIT', '128M');
if (function_exists('memory_get_usage') && (( int )@ini_get('memory_limit') < abs(intval(RUNTIME_MEMORY_LIMIT)))) {
    @ini_set('memory_limit', RUNTIME_MEMORY_LIMIT);
}
if (!function_exists('mb_internal_encoding')) {
    !trigger_error('mb_string extension is required!') or exit(1);
}
if (!function_exists('json_decode')) {
    !@trigger_error('json extension is required!') or exit(1);
}
if (!function_exists('spl_autoload_register')) {
    !trigger_error('SPL extension is required!') or exit(1);
}
if (!function_exists('curl_init')) {
    !trigger_error('curl extension is required!') or exit(1);
}
// 全局环境配置
@ini_set('session.bug_compat_warn', 0);
@ini_set('session.bug_compat_42', 0);
@mb_internal_encoding('UTF-8');
@mb_regex_encoding('UTF-8');
@mb_http_output('UTF-8');
// 类路径配置
/** @global string[] $_wula_classpath none-namespace classpath. */
global $_wula_classpath;
/** @global string[] $_wula_namespace_classpath psr-4 classpath. */
global $_wula_namespace_classpath;
$_wula_namespace_classpath [] = WULA_ROOT;
if (is_dir(EXTENSIONS_PATH)) {
    $_wula_namespace_classpath [] = EXTENSIONS_PATH;
}
if (is_dir(WEB_ROOT . VENDOR_DIR)) {
    $_wula_namespace_classpath [] = WEB_ROOT . VENDOR_DIR . DS;
}
$_wula_namespace_classpath [] = WULA_ROOT . 'vendors' . DS;
$_wula_classpath []           = WULA_ROOT . 'vendors' . DS;
// 基础类文件加载
include WULA_ROOT . 'wulaphp/conf/Configuration.php';
include WULA_ROOT . 'wulaphp/conf/CacheConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/ClusterConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/RedisConfiguration.php';
include WULA_ROOT . 'wulaphp/conf/BaseConfigurationLoader.php';
include WULA_ROOT . 'wulaphp/conf/ConfigurationLoader.php';
include WULA_ROOT . 'wulaphp/cache/Cache.php';
include WULA_ROOT . 'wulaphp/cache/RedisCache.php';
include WULA_ROOT . 'wulaphp/cache/MemcachedCache.php';
include WULA_ROOT . 'wulaphp/cache/RtCache.php';
include WULA_ROOT . 'wulaphp/util/ObjectCaller.php';
//运行环境检测
if (!defined('APP_MODE')) {
    if (isset($_SERVER['APPMODE']) && $_SERVER['APPMODE']) {
        define('APP_MODE', $_SERVER['APPMODE']);
    } else if (isset($_ENV['APPMODE']) && $_ENV['APPMODE']) {
        define('APP_MODE', $_ENV['APPMODE']);
    } else {
        define('APP_MODE', env('app_mode', 'dev'));
    }
}
//启动运行时缓存
RtCache::init();
//注册类自动加载
spl_autoload_register(function ($clz) {
    global $_wula_classpath, $_wula_namespace_classpath;
    $key      = $clz . '.class';
    $clz_file = RtCache::get($key);
    if ($clz_file && is_file($clz_file)) {
        include $clz_file;

        return;
    }
    if (strpos($clz, '\\') > 0) {
        $clzf = str_replace('\\', DS, $clz);
        foreach ($_wula_namespace_classpath as $cp) {
            $clz_file = $cp . $clzf . '.php';
            if (is_file($clz_file)) {
                RtCache::add($key, $clz_file);
                include $clz_file;

                return;
            }
        }
        //从模块加载
        $clz_file = App::loadClass($clz);
        if ($clz_file && is_file($clz_file)) {
            RtCache::add($key, $clz_file);
            include $clz_file;

            return;
        }
    }
    foreach ($_wula_classpath as $path) {
        $clz_file = $path . DS . $clz . '.php';
        if (is_file($clz_file)) {
            RtCache::add($key, $clz_file);
            include $clz_file;

            return;
        }
    }
    $clz_file = apply_filter('loader\loadClass', null, $clz);
    if ($clz_file && is_file($clz_file)) {
        RtCache::add($key, $clz_file);
        include $clz_file;
    }
});
include WULA_ROOT . 'includes/common.php';
if (is_file(LIBS_PATH . 'common.php')) {
    include LIBS_PATH . 'common.php';
}
App::start();
define('WULA_BOOTSTRAPPED', microtime(true));
fire('wula\bootstrapped');
//end of bootstrap.php