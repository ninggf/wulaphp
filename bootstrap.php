<?php
use wulaphp\app\App;
use wulaphp\cache\RtCache;

/**
 * bootstrap file of wula framework.
 *
 * @author leo
 */
defined('APPROOT') or die ('please define APPROOT');
/**
 * 版本号.
 *
 * @var string
 */
define('WULA_VERSION', '0.1.0');
/**
 * 发行标记.
 *
 * @var string
 */
define('WULA_RELEASE', 'dev');
/* 常用目录定义 */
define('WULA_ROOT', __DIR__ . DS);

defined('MODULE_DIR') or define('MODULE_DIR', 'modules');
defined('EXTENSION_DIR') or define('EXTENSION_DIR', 'extensions');
defined('THEME_DIR') or define('THEME_DIR', 'themes');
defined('CONF_DIR') or define('CONF_DIR', 'conf');
defined('LIBS_DIR') or define('LIBS_DIR', 'includes');
defined('VENDORS_DIR') or define('VENDORS_DIR', 'vendor');
defined('PLUGINS_DIR') or define('PLUGINS_DIR', 'plugins');
defined('APPNAME') or define('APPNAME', basename(APPROOT));
if (defined('WWWROOT')) {
	define('WWWROOT_DIR', basename(WWWROOT));
} else {
	defined('WWWROOT_DIR') or define('WWWROOT_DIR', 'wwwroot');
	defined('WWWROOT') or define('WWWROOT', APPROOT . WWWROOT_DIR . DS);
}

define('WEB_ROOT', WWWROOT);

define('LIBS_PATH', APPROOT . LIBS_DIR . DS);
defined('TMP_PATH') or define('TMP_PATH', APPROOT . 'tmp' . DS);
define('MODULES_PATH', WWWROOT . MODULE_DIR . DS);
define('EXTENSIONS_PATH', APPROOT . EXTENSION_DIR . DS);
define('THEME_PATH', WWWROOT . THEME_DIR . DS);
define('MODULE_ROOT', MODULES_PATH);
/* 定义加载器类 */
defined('MODULE_LOADER_CLASS') or define('MODULE_LOADER_CLASS', '\wulaphp\app\ModuleLoader');
defined('EXTENSION_LOADER_CLASS') or define('EXTENSION_LOADER_CLASS', '\wulaphp\app\ExtensionLoader');
defined('CONFIG_LOADER_CLASS') or define('CONFIG_LOADER_CLASS', '\wulaphp\conf\ConfigurationLoader ');
/* 定义日志级别 */
define('DEBUG_OFF', 5);
define('DEBUG_ERROR', 4);
define('DEBUG_INFO', 3);
define('DEBUG_WARN', 2);
define('DEBUG_DEBUG', 1);

// 过滤输入
if (@ini_get('register_globals')) {
	die ('please close "register_globals" in php.ini file.');
}
if (version_compare('5.6.0', phpversion(), '>')) {
	die (sprintf('Your php version is %s,but wulaphp required  PHP 5.6.0 or higher', phpversion()));
}
// 运行时间
if (defined('MAX_RUNTIME_LIMIT')) {
	set_time_limit(intval(MAX_RUNTIME_LIMIT));
}
// 运行内存
if (!defined('RUNTIME_MEMORY_LIMIT')) {
	define('RUNTIME_MEMORY_LIMIT', '128M');
}
if (function_exists('memory_get_usage') && (( int )@ini_get('memory_limit') < abs(intval(RUNTIME_MEMORY_LIMIT)))) {
	@ini_set('memory_limit', RUNTIME_MEMORY_LIMIT);
}
// 必须安装 mb_string
if (!function_exists('mb_internal_encoding')) {
	die ('mb_string extension is required!');
}
// 必须安装 json
if (!function_exists('json_decode')) {
	die ('json extension is required!');
}
// 必须安装 SPL
if (!function_exists('spl_autoload_register')) {
	die ('SPL extension is required!');
}
// 必须安装 curl
if (!function_exists('curl_init')) {
	die ('curl extension is required!');
}

/* 开启缓冲区 (特别重要) */
@ob_start();

/* 应用编码只支持UTF8 */
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
/* 关掉session以下二个特性 */
@ini_set('session.bug_compat_warn', 0);
@ini_set('session.bug_compat_42', 0);
/* 类自动加载与注册类自动加载函数. */
global $_wula_classpath;
$_wula_classpath = array();
global $_wula_namespace_classpath;
$_wula_namespace_classpath    = array();
$_wula_namespace_classpath [] = APPROOT . EXTENSION_DIR . DS;
$_wula_namespace_classpath [] = APPROOT . LIBS_DIR . DS . 'classes' . DS;
$_wula_namespace_classpath [] = WULA_ROOT . 'vendors' . DS;

/* 加载运行时缓存 */
include WULA_ROOT . 'wulaphp/cache/Cache.php';
include WULA_ROOT . 'wulaphp/cache/ApcCacher.php';
include WULA_ROOT . 'wulaphp/cache/XCacheCacher.php';
include WULA_ROOT . 'wulaphp/cache/RtCache.php';
/* 注册类自定义加载函数 */
spl_autoload_register(function ($clz) {
	$key      = $clz . '.class';
	$clz_file = RtCache::get($key);
	if (is_file($clz_file)) {
		include $clz_file;

		return;
	}
	if (strpos($clz, '\\') > 0) {
		global $_wula_namespace_classpath;
		if (defined('WULA_BOOTSTRAPPED')) {
			$clz_file = App::loadClass($clz);
			if ($clz_file && is_file($clz_file)) {
				RtCache::add($key, $clz_file);
				include $clz_file;

				return;
			}
		}
		$clzf = str_replace('\\', DS, $clz);
		foreach ($_wula_namespace_classpath as $cp) {
			$clz_file = $cp . $clzf . '.php';
			if (is_file($clz_file)) {
				RtCache::add($key, $clz_file);
				include $clz_file;

				return;
			}
		}
	}
	global $_wula_classpath;
	foreach ($_wula_classpath as $path) {
		$clz_file = $path . DS . $clz . '.php';
		if (is_file($clz_file)) {
			RtCache::add($key, $clz_file);
			include $clz_file;

			return;
		}
	}
	// 处理未找到类情况.
});

/* 加载第三方函数库 */
if (is_file(APPROOT . LIBS_DIR . '/common.php')) {
	require APPROOT . LIBS_DIR . '/common.php';
}
require WULA_ROOT . 'includes/common.php';

App::start();
define('WULA_BOOTSTRAPPED', microtime(true));
//end of bootstrap.php