<?php
/* 是否开启gzip压缩 */
define('GZIP_ENABLED', true);
/* 运行模式 */
# define('APP_MODE', 'pro');
/* 配置系统的默认模块配置,请取消下一行的注释，将其值改为模块命名空间 */
# define('DEFAULT_MODULE', 'app');
/* 如果需要开启URL别名请取消下一行注释，并配置modules/alias.php */
# define('ALIAS_ENABLED', true);
/* 如果你的网站以集群的方式提供服务时，请取消下一行的注释，并配置cluster_config.php */
# define('RUN_IN_CLUSTER', true);
/* 如果你的应用不是运行在网站的根目录,请取消下一行注释并修改其值,必须以/开始,以/结束。*/
# define('WWWROOT_DIR', '/');
/* 如果你的网站对外目录不是wwwroot,请取消下一行注释并修改其值。*/
# define('PUBLIC_DIR', 'wwwroot');
/* 如果你想改变assets目录名，请联消下一行注释并修改其值 */
# define('ASSETS_DIR', 'assets');
/* 如果你想改modules目录名，请取消下一行注释并修改其值. */
# define ('MODULE_DIR', 'modules');
/* 如果你想改themes目录名，请取消下一行注释并修改其值. */
# define('THEME_DIR', 'themes');
/* 如果你想改extensions目录名，请取消下一行注释并修改其值. */
# define('EXTENSION_DIR', 'extensions');
/* 如果你想改conf目录名，请取消下一行注释并修改其值. */
# define ('CONF_DIR', 'conf');
/* 如果你想改libs目录名，请取消下一行注释并修改其值. */
# define ('LIBS_DIR', 'includes');
/* 如果你想改storage目录名，请取消下一行注释并修改其值. */
# define ('STORAGE_DIR', 'storage');
/* 如果你想改tmp目录名，请取消下一行注释并修改其值. */
# define ('TMP_DIR', 'tmp');
/* 如果你想改logs目录名，请取消下一行注释并修改其值. */
# define ('LOGS_DIR', 'logs');
/* 重新定义运行时内存限制 */
# define ('RUNTIME_MEMORY_LIMIT', '128M');
/* 如果你要重新定义扩展加载器,请修改 */
# define('EXTENSION_LOADER_CLASS', 'wulaphp\app\ExtensionLoader');
/* 如果你要重新定义配置加载器,请修改 */
# define('CONFIG_LOADER_CLASS', 'wulaphp\conf\ConfigurationLoader');
/* 如果你要重新定义模块加载器,请 */
# define('MODULE_LOADER_CLASS', 'wulaphp\app\ModuleLoader');
// 以上配置选择性修改
// //////////////////////////////////////////////////////////////////////////////
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!以下内容不可修改!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// //////////////////////////////////////////////////////////////////////////////
define('APPROOT', __DIR__ . DIRECTORY_SEPARATOR);
defined('PUBLIC_DIR') or define('PUBLIC_DIR', 'wwwroot');
if (!defined('WWWROOT')) {
    define('WWWROOT', APPROOT . PUBLIC_DIR . DIRECTORY_SEPARATOR);
}
if (defined('PHPUNIT_COMPOSER_INSTALL')) {
    require APPROOT . 'vendor' . DIRECTORY_SEPARATOR . 'wula/wulaphp/bootstrap.php';
} else {
    require APPROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}
// end of bootstrap.php