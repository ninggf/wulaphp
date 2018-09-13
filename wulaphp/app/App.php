<?php

namespace wulaphp\app;

use wulaphp\conf\Configuration;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\conf\DatabaseConfiguration;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\SimpleTable;
use wulaphp\i18n\I18n;
use wulaphp\io\Response;
use wulaphp\router\Router;
use wulaphp\util\ObjectCaller;

/**
 * 应用程序类,负责加载模块,配置,并启动路由器.
 *
 * @package wulaphp\app
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 */
class App {
    private $configs = [];//配置组
    /**
     * @var Router
     */
    private $router;
    /**
     * @var ConfigurationLoader
     */
    private $configLoader;
    /**
     * @var ExtensionLoader
     */
    private $extensionLoader;
    /**
     * @var ModuleLoader
     */
    private $moduleLoader;
    /**
     * 目录映射.
     * @var array
     */
    private static $maps           = ['dir2id' => [], 'id2dir' => []];
    private static $modules        = [];//模块
    private static $extensions     = [];//扩展
    private static $enabledModules = [];//启用的模块
    public static  $prefix         = [];//URL前缀

    /**
     * @var App
     */
    private static $app = null;

    /**
     * App constructor.
     * @throws \Exception 无法加载配置、模块、扩展加载器时
     */
    private function __construct() {
        self::$app = $this;
        /* 加载配置文件 */
        $clz = trim(CONFIG_LOADER_CLASS);
        if (class_exists($clz)) {
            $configLoader = new $clz();
        } else if ($clz == 'wulaphp\conf\ConfigurationLoader') {
            $configLoader = new ConfigurationLoader ();
        } else {
            throw new \Exception('cannot find configuration loader: ' . $clz);
        }
        if ($configLoader instanceof ConfigurationLoader) {
            $this->configLoader = $configLoader;
            $configLoader->beforeLoad();
            $this->configs ['default'] = $configLoader->loadConfig();
            $configLoader->postLoad();
        } else {
            throw new \Exception('no ConfigurationLoader found!');
        }
        fire('wula\configLoaded');//配置加载完成
        //检测语言
        if (!defined('LANGUAGE')) {
            $lang        = isset($_COOKIE['language']) ? $_COOKIE['language'] : null;
            $defaultLang = $this->configs ['default']->get('language');
            if (preg_match('/^[a-z]{2,3}(-[A-Z]{2,8})?$/i', $lang)) {
                //用户设置通过cookie设置
                define('LANGUAGE', $lang);
            } else if (preg_match('/^[a-z]{2,3}(-[a-z]{2,8})?$/i', $defaultLang)) {
                //系统默认
                define('LANGUAGE', $defaultLang);
            } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                //浏览器默认
                define('LANGUAGE', explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]);
            } else {
                //框架默认
                define('LANGUAGE', 'en');
            }
        }
        //加载语言
        I18n::addLang(WULA_ROOT . 'lang');
        // 加载扩展
        $clz = trim(EXTENSION_LOADER_CLASS);
        if (class_exists($clz)) {
            $extensionLoader = new $clz();
        } else {
            $extensionLoader = new ExtensionLoader();
        }
        if ($extensionLoader instanceof ExtensionLoader) {
            $this->extensionLoader = $extensionLoader;
            $this->extensionLoader->load();
        } else {
            throw new \Exception('No ExtensionLoader found!');
        }
        fire('wula\extensionLoaded');//扩展加载完成
        // 加载模块
        $clz = trim(MODULE_LOADER_CLASS);
        if (class_exists($clz)) {
            $moduleLoader = new $clz();
        } else if ($clz == 'wulaphp\app\ModuleLoader') {
            $moduleLoader = new ModuleLoader();
        } else {
            throw new \Exception('cannot find module loader: ' . $clz);
        }
        if ($moduleLoader instanceof ModuleLoader) {
            $this->moduleLoader = $moduleLoader;
            $this->moduleLoader->load();
        } else {
            throw new \Exception('No ModuleLoader found!');
        }
        fire('wula\moduleLoaded');//模块加载完成
    }

    /**
     * 获取系统配置加载器.
     *
     * @return \wulaphp\conf\ConfigurationLoader
     * @throws
     */
    public static function cfgLoader() {
        if (!self::$app) {
            new App ();
        }

        return self::$app->configLoader;
    }

    /**
     * 获取系统模块加载器.
     *
     * @return \wulaphp\app\ModuleLoader
     * @throws
     */
    public static function moduleLoader() {
        if (!self::$app) {
            new App ();
        }

        return self::$app->moduleLoader;
    }

    /**
     * 启动App.
     *
     * @return App
     * @throws
     */
    public static function start() {
        if (!self::$app) {
            new App ();
        }
        $debug = App::icfg('debug', DEBUG_ERROR);
        if ($debug > 1000 || $debug < 0) {
            $debug = DEBUG_OFF;
        }
        define('DEBUG', $debug);
        if (DEBUG == DEBUG_OFF) {
            define('KS_ERROR_REPORT_LEVEL', E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
            @ini_set('display_errors', 0);
        } else if (DEBUG == DEBUG_DEBUG) {
            define('KS_ERROR_REPORT_LEVEL', E_ALL & ~E_NOTICE);
            @ini_set('display_errors', 1);
        } else {
            define('KS_ERROR_REPORT_LEVEL', E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
            @ini_set('display_errors', 1);
        }
        error_reporting(KS_ERROR_REPORT_LEVEL);
        $timezone = App::cfg('timezone', 'Asia/Shanghai');
        // 时区设置
        define('TIMEZONE', $timezone);
        date_default_timezone_set(TIMEZONE);
        foreach (self::$extensions as $extension) {
            $extension->autoBind();
        }
        foreach (self::$modules as $id => $module) {
            if (self::$app->moduleLoader->isEnabled($module)) {
                self::$enabledModules[ $id ] = $module;
                if (method_exists($module->clzName, 'urlGroup')) {
                    $prefix = ObjectCaller::callClzMethod($module->clzName, 'urlGroup');
                    if ($prefix && $prefix[0]) {
                        self::registerUrlGroup($prefix, $module->getNamespace());
                    }
                }
                $module->autoBind();
            }
        }

        return self::$app;
    }

    /**
     * @param string $status
     *
     * @return \wulaphp\app\Module[]
     */
    public static function modules($status = 'installed') {
        switch ($status) {
            case 'uninstalled':
                $modules = array_filter(self::$modules, function (Module $m) {
                    return !$m->installed;
                });
                break;
            case 'installed':
                $modules = array_filter(self::$modules, function (Module $m) {
                    return $m->installed;
                });
                break;
            case 'enabled':
                $modules = array_filter(self::$modules, function (Module $m) {
                    return $m->enabled;
                });
                break;
            case 'disabled':
                $modules = array_filter(self::$modules, function (Module $m) {
                    return !$m->enabled && $m->installed;
                });
                break;
            case 'upgradable':
                $modules = array_filter(self::$modules, function (Module $m) {
                    return $m->upgradable && $m->installed;
                });
                break;
            default:
                $modules = self::$modules;
                break;
        }

        return $modules;
    }

    /**
     * 获取数据库连接实例.
     *
     * @param string|array|DatabaseConfiguration|null $name 数据库配置名/配置数组/配置实例.
     *
     * @return DatabaseConnection
     * @throws \Exception
     */
    public static function db($name = 'default') {

        return DatabaseConnection::connect($name);
    }

    /**
     * 快速获取一个简单表.
     *
     * @param string                    $table 表名.
     * @param string|DatabaseConnection $db    表所在的数据库.
     *
     * @return \wulaphp\db\SimpleTable|null
     */
    public static function table($table, $db = 'default') {
        static $tables = [];
        try {
            $dbid = get_unique_id($db);
            if (!isset($tables[ $dbid ][ $table ])) {
                $tables[ $dbid ][ $table ] = new SimpleTable($table, $db);
            }

            return $tables[ $dbid ][ $table ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取配置.
     *
     * @param string $config 配置组.
     * @param bool   $file   仅从配置文件读取.
     *
     * @return \wulaphp\conf\Configuration
     */
    public static function config($config, $file = false) {
        $app = self::$app;
        if (isset ($app->configs [ $config ])) {
            $confObj = $app->configs [ $config ];
        } else {
            $confObj = $file ? ConfigurationLoader::loadFromFile($config) : $app->configLoader->loadConfig($config);
            if (!$confObj) {
                $confObj = new Configuration ($config);
            }
            $app->configs [ $config ] = $confObj;
        }

        return $confObj;
    }

    /**
     * 读取配置.
     *
     * @param string $name    配置项名与组名,例abc@def表示从def配置中读取abc,如果不指定组,则默认为default.
     * @param mixed  $default 默认值.
     *
     * @return mixed 配置值.
     */
    public static function cfg($name = '@default', $default = '') {
        $app  = self::$app;
        $keys = null;
        if ($name != null) {
            $names = explode('@', $name);
            if ($names[0]) {
                $name = $names[0];
                $keys = explode('.', $name);
                $name = array_shift($keys);
            } else {
                $name = null;
            }
            if (isset($names[1]) && $names[1]) {
                $config = $names[1];
            } else {
                $config = 'default';
            }
        } else {
            $config = 'default';
        }
        if (isset ($app->configs [ $config ])) {
            $confObj = $app->configs [ $config ];
        } else {
            $confObj = $app->configLoader->loadConfig($config);
            if (!$confObj) {
                $confObj = new Configuration ($config);
            }
            $app->configs [ $config ] = $confObj;
        }

        if ($name == null) {
            return $confObj;
        } else {
            $values = $confObj->get($name, $default);
            if ($keys && is_array($values)) {
                foreach ($keys as $ky) {
                    if (isset($values[ $ky ])) {
                        $values = $values[ $ky ];
                    }
                }
            }

            return $values;
        }
    }

    /**
     * @param string $name    配置项名与组名,例abc@def表示从def配置中读取abc,如果不指定组,则默认为default.
     * @param bool   $default 默认值为false.
     *
     * @return bool
     */
    public static function bcfg($name, $default = false) {
        if ($name == null) {
            return false;
        } else {
            $val = App::cfg($name, $default);
            if (is_bool($val)) {
                return $val;
            }
            if (empty ($val) || $val == 'false' || $val == '0') {
                return false;
            }

            return true;
        }
    }

    /**
     * 取整数配置.
     *
     * @param string $name    配置项名与组名,例abc@def表示从def配置中读取abc,如果不指定组,则默认为default.
     * @param int    $default 默认值为0.
     *
     * @return int
     */
    public static function icfg($name, $default = 0) {
        if ($name == null) {
            return $default;
        } else {
            $val = App::cfg($name, $default);

            return intval($val);
        }
    }

    /**
     * 取整数配置。如果配置为0则返回$default。
     *
     * @param string $name    配置项名与组名,例abc@def表示从def配置中读取abc,如果不指定组,则默认为default.
     * @param int    $default 默认值为0.
     *
     * @return int
     */
    public static function icfgn($name, $default = 0) {
        $val = self::icfg($name, $default);
        if (!$val) {
            return $default;
        }

        return $val;
    }

    /**
     * 取数组.
     *
     * @param string $name
     * @param array  $default
     *
     * @return array
     */
    public static function acfg($name, array $default = []) {
        $value = self::cfg($name);
        if ($value) {
            if (is_string($value)) {
                $value = @json_decode($value, true);
            }
            if ($value === false) {
                $value = [];
            }
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * 注册模块.
     *
     * @param Module $module
     */
    public static function register(Module $module) {
        $name = $module->getNamespace();
        if ($name == 'wulaphp') {
            $msg = __('the namespace of %s cannot be wulaphp', $module->clzName);
            log_warn($msg, 'bootstrap');

            return;
        }
        if (!preg_match('/^[a-z][a-z_\d]+(\\\\[a-z][a-z_\d]+)*$/i', $name)) {
            $msg = __('The namespace "%s" of %s is invalid', $name, $module->clzName);
            log_warn($msg, 'bootstrap');

            return;
        }
        $dir = $module->getDirname();
        if ($dir != $name) {
            self::$maps ['dir2id'] [ $dir ]  = $name;
            self::$maps ['id2dir'] [ $name ] = $dir;
        }
        self::$modules [ $name ] = $module;
        $module->enabled         = self::$app->moduleLoader->isEnabled($module);
    }

    /**
     * @param Extension $extension
     */
    public static function registerExtension(Extension $extension) {
        self::$extensions[] = $extension;
    }

    /**
     * 获取模块信息.
     *
     * @param string $module namespace of the module.
     *
     * @return Module|null 未找到时返回Null
     */
    public static function getModule($module) {
        $info = null;
        if (isset (self::$modules [ $module ])) {
            return self::$modules [ $module ];
        }

        return $info;
    }

    /**
     * alias of getModule.
     *
     * @param string $id namespace of the module
     *
     * @return Module|null
     */
    public static function getModuleById($id) {
        return self::getModule($id);
    }

    /**
     * 根据目录查找模块.
     *
     * @param string $dir the dir of the module.
     *
     * @return Module|null
     */
    public static function getModuleByDir($dir) {
        $id = self::dir2id($dir);

        return self::getModule($id);
    }

    /**
     * 根据目录名查找模块id(namespace)
     *
     * @param string $dir
     * @param bool   $check 如果为true,模块未加载或不存在时返回null.
     *
     * @return string|null 未找到时返回null.
     */
    public static function dir2id($dir, $check = false) {
        if (isset (self::$maps ['dir2id'] [ $dir ])) {
            return self::$maps ['dir2id'] [ $dir ];
        } else if (!$check) {
            return $dir;
        } else if ($check && isset (self::$modules [ $dir ])) {
            return $dir;
        }

        return null;
    }

    /**
     * 根据模块ID（namespace）查找模块所在目录.当$id为null时，返回映射字典.
     *
     * @param string $id 模块id
     *
     * @return string|string[]
     */
    public static function id2dir($id = null) {
        if ($id === null) {
            return self::$maps ['id2dir'];
        }
        if (isset (self::$maps ['id2dir'] [ $id ])) {
            return self::$maps ['id2dir'] [ $id ];
        } else {
            return $id;
        }
    }

    /**
     * @param array  $prefix
     * @param string $namespace
     */
    private static function registerUrlGroup(array $prefix, $namespace) {
        if (isset($prefix[0]) && isset($prefix[1])) {
            $char = $prefix[0];
            if (!in_array($char, ['~', '!', '@', '#', '%', '^', '&', '*'])) {
                throw new \InvalidArgumentException($char . ' is invalid, the valid chars are "~,!,@,#,%,^,&,*"');
            }
            $p = $prefix[1];
            if (!preg_match('#^[a-z][a-z\d_-]*$#', $p)) {
                throw new \InvalidArgumentException($p . ' is invalid, the valid prefix must be matched ^[a-z][a-z\d_-]*$');
            }
            self::$prefix['char'][]      = $char;
            self::$prefix['prefix'][]    = $p . '/';
            self::$prefix['check'][ $p ] = $namespace;
        } else {
            throw new \InvalidArgumentException('prefix is invalid');
        }
    }

    /**
     * @param string $prefix
     *
     * @return null|string
     */
    public static function checkUrlPrefix($prefix) {
        return isset(self::$prefix['check'][ $prefix ]) ? self::$prefix['check'][ $prefix ] : null;
    }

    /**
     * 重定向.
     *
     * @param string     $url
     * @param array|null $args [optional]
     */
    public static function redirect($url, $args = null) {
        if (strpos($url, '\\') !== false) {
            $url = self::action($url);
        } else {
            $url = self::url($url);
        }
        if (!empty($args)) {
            $url .= '?' . http_build_query($args);
        }
        Response::redirect($url, '');
    }

    /**
     * 生成美丽的URL.
     *
     * @param string $url
     *
     * @return string
     */
    public static function base($url) {
        if (preg_match('#^(/|(ht|f)tps?://).+#', $url)) {
            return $url;
        }
        $url = preg_replace('#/?index\.html?$#', '', $url);

        return WWWROOT_DIR . $url;
    }

    /**
     * 生成模块url.
     *
     * @param string $url
     * @param bool   $replace
     *
     * @return string
     */
    public static function url($url, $replace = true) {
        static $alias = false, $defaultModule = false;
        if ($alias === false) {
            if (defined('ALIAS_ENABLED') && ALIAS_ENABLED) {
                $aliasFile = MODULES_PATH . 'alias.php';
                if (is_file($aliasFile)) {
                    $alias = (array)include $aliasFile;
                } else {
                    $alias = [];
                }
            } else {
                $alias = [];
            }
        }
        if ($defaultModule === false) {
            if (defined('DEFAULT_MODULE')) {
                $defaultModule = self::id2dir(DEFAULT_MODULE);
            } else {
                $defaultModule = '';
            }
        }
        $url = trim($url, '/');

        $urls = explode('/', $url);
        if ($replace) {
            if (preg_match('/^([~!@#%\^&\*])(.+)$/', $urls[0], $ms)) {
                $urls[0] = $ms[1] . App::id2dir($ms[2]);
            } else {
                $urls[0] = App::id2dir($urls[0]);
            }
        }
        if (self::$prefix) {
            $urls[0] = str_replace(self::$prefix['char'], self::$prefix['prefix'], $urls[0]);
        }
        $rurl = implode('/', $urls);
        //检测别名
        if ($rurl) {
            $key = array_search($rurl, $alias);
            if ($key) {
                $rurl = trim($key, '/');
            }
        }

        //检测默认模块
        if ($defaultModule && preg_match("#^$defaultModule#", $rurl)) {
            //去掉默认模块路径
            $rurl = trim(substr($rurl, strlen($defaultModule)), '/');
        }

        return WWWROOT_DIR . $rurl;
    }

    /**
     * 生成模块url的Hash方式.
     *
     * @param string $url
     * @param bool   $replace
     *
     * @return string
     */
    public static function hash($url, $replace = true) {
        return "#" . self::url($url, $replace);
    }

    /**
     * @param string $url 控制器全类名::方法名
     *
     * @return string
     */
    public static function action($url) {
        static $prefixes = [];

        $clz = trim($url);
        if (!$clz) {
            return '#';
        }

        $urls = explode('::', $clz);

        $action = '';

        if (count($urls) == 2) {
            $clz    = trim(trim($urls[0]), '\\');
            $action = $urls[1] != 'index' ? $urls[1] : '';
        }

        if (!isset($prefixes[ $clz ])) {
            $clzs    = explode('\\', $clz);
            $id      = $clzs[0];
            $clzs[0] = App::id2dir($id);
            $file    = MODULES_PATH . implode(DS, $clzs) . '.php';
            if (!is_file($file)) {
                return '#';
            } else {
                include_once $file;
                if (!class_exists($clz) || !is_subclass_of($clz, 'wulaphp\mvc\controller\Controller')) {
                    return '#';
                }
            }
            $ctrClz = array_pop($clzs);
            array_pop($clzs);

            $ctr = preg_replace('#Controller$#', '', $ctrClz);
            $ctr = Router::addSlash($ctr);

            if ('index' != $ctr) {
                array_push($clzs, $ctr);
            }

            $prefix = '';
            if (method_exists($clz, 'urlGroup')) {
                $tprefix = ObjectCaller::callClzMethod($clz, 'urlGroup');
                if ($tprefix && isset($tprefix[0])) {
                    $prefix = $tprefix[0];
                }
            }

            $prefixes[ $clz ] = $prefix . implode('/', $clzs);
        }
        if ($action && $action != 'index') {
            return self::url($prefixes[ $clz ] . '/' . Router::addSlash($action), false);
        } else {
            return self::url($prefixes[ $clz ], false);
        }
    }

    /**
     * 模块资源url.
     *
     * @param string $res
     * @param string $min
     *
     * @return string
     */
    public static function res($res, $min = '') {
        $static_url = WWWROOT_DIR;
        $url        = ltrim($res, '/');
        if ($min || APP_MODE == 'pro') {
            $url1 = preg_replace('#\.(js|css)$#i', '.min.\1', $url);
            if (is_file(WWWROOT . ASSETS_DIR . '/' . $url1)) {
                $url = $url1;
            }
        }

        return $static_url . ASSETS_DIR . '/' . $url;
    }

    /**
     * @param string $src src
     *
     * @return string
     */
    public static function src($src) {
        static $static_url = false;
        if ($static_url === false) {
            $static_url = App::cfg('static_base');
            if ($static_url) {
                $static_url = trailingslashit($static_url . WWWROOT_DIR);
            } else {
                $static_url = WWWROOT_DIR;
            }
        }

        return $static_url . $src;
    }

    /**
     * 全站assets资源URL.
     *
     * @param string $res
     * @param string $min
     *
     * @return string
     */
    public static function assets($res, $min = '') {
        static $static_url = false;
        if ($static_url === false) {
            $static_url = App::cfg('static_base');
            if ($static_url) {
                $static_url = trailingslashit($static_url . WWWROOT_DIR);
            } else {
                $static_url = WWWROOT_DIR;
            }
        }
        $url = ltrim($res, '/');
        if ($min || APP_MODE == 'pro') {
            $url1 = preg_replace('#\.(js|css)$#i', '.min.\1', $url);
            if (is_file(WWWROOT . ASSETS_DIR . '/' . $url1)) {
                $url = $url1;
            }
        }

        return $static_url . ASSETS_DIR . '/' . $url;
    }

    /**
     * 全站cdn资源URL.
     *
     * @param string $res
     * @param string $min
     *
     * @return string
     */
    public static function cdn($res, $min = '') {
        static $static_url = false;
        if ($static_url === false) {
            $static_url = App::cfg('cdn_base');
            if ($static_url) {
                $static_url = trailingslashit($static_url . WWWROOT_DIR);
            } else {
                $static_url = WWWROOT_DIR;
            }
        }
        $url = ltrim($res, '/');
        if ($min || APP_MODE == 'pro') {
            $url1 = preg_replace('#\.(js|css)$#i', '.min.\1', $url);
            if (is_file(WWWROOT . $url1)) {
                $url = $url1;
            }
        }

        return $static_url . $url;
    }

    /**
     * 第三方js，css图片等资源.
     *
     * @param string $res
     * @param string $min
     *
     * @return string
     */
    public static function vendor($res, $min = '') {
        static $static_url = false;
        if ($static_url === false) {
            $static_url = App::cfg('static_base');
            if ($static_url) {
                $static_url = trailingslashit($static_url . WWWROOT_DIR);
            } else {
                $static_url = WWWROOT_DIR;
            }
        }
        $url = ltrim($res, '/');
        if ($min || APP_MODE == 'pro') {
            $url1 = preg_replace('#\.(js|css)$#i', '.min.\1', $url);
            if (is_file(WWWROOT . VENDOR_DIR . '/' . $url1)) {
                $url = $url1;
            }
        }

        return $static_url . VENDOR_DIR . '/' . $url;
    }

    /**
     * 加载模块中定义的类.
     *
     * @param string $cls
     *
     * @return mixed 类文件全路径.
     */
    public static function loadClass($cls) {
        $pkg = explode('\\', $cls);
        if (count($pkg) > 1) {
            $module = $pkg [0];
            if (!isset(self::$modules[ $module ])) {
                $module .= '\\' . $pkg[1];
                array_shift($pkg);
                if (!isset(self::$modules[ $module ])) {
                    return null;
                }
            }
            if (isset (self::$maps ['id2dir'] [ $module ])) {
                $pkg [0] = self::$maps ['id2dir'] [ $module ];
            }
            $file = implode(DS, $pkg) . '.php';

            return self::$app->moduleLoader->loadClass($file);
        } else {
            return null;
        }
    }

    /**
     * 启动APP处理请求.
     *
     * @param string $url    请求URL.
     * @param string $method 请求方法.
     *
     * @return mixed
     */
    public static function run($url = '', $method = 'GET') {
        if ($url) {
            $_SERVER['REQUEST_URI']    = $url;
            $_SERVER['REQUEST_METHOD'] = $method;
        }
        if (!isset ($_SERVER ['REQUEST_URI'])) {
            Response::respond(500, 'Your web server did not provide REQUEST_URI, stop route request.');
        }
        if (!self::$app->router) {
            self::$app->router = Router::getRouter();
        }
        try {
            return self::$app->router->route();
        } catch (\Exception $e) {
            Response::respond(500, $e->getMessage());
        }

        return null;
    }
}