<?php
namespace wulaphp\app;

use wulaphp\conf\Configuration;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\DialectException;
use wulaphp\db\SimpleTable;
use wulaphp\i18n\I18n;
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

	private $configs = array();
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
	private static $modules        = [];
	private static $extensions     = [];
	private static $enabledModules = [];
	private static $prefix         = [];
	/**
	 * @var App
	 */
	private static $app = null;

	private function __construct() {
		/* 加载配置文件 */
		$clz = CONFIG_LOADER_CLASS;
		if (class_exists($clz)) {
			$configLoader = new $clz();
		} else {
			$configLoader = new ConfigurationLoader ();
		}
		if ($configLoader instanceof ConfigurationLoader) {
			$configLoader->beforeLoad();
			$this->configs ['default'] = $configLoader->loadConfig();
			$configLoader->postLoad();
			$this->configLoader = $configLoader;
		} else {
			throw new \Exception('no ConfigurationLoader found!');
		}
		I18n::addLang(WULA_ROOT . 'lang');
		// 加载扩展
		$clz = EXTENSION_LOADER_CLASS;
		if (class_exists($clz)) {
			$extensionLoader = new $clz();
		} else {
			$extensionLoader = new ExtensionLoader();
		}
		if ($extensionLoader instanceof ExtensionLoader) {
			$this->extensionLoader = $extensionLoader;
			$this->extensionLoader->load();
		} else {
			throw new \Exception('no ExtensionLoader found!');
		}
		// 加载模块
		$clz = MODULE_LOADER_CLASS;
		if (class_exists($clz)) {
			$moduleLoader = new $clz();
		} else {
			$moduleLoader = new ModuleLoader();
		}
		if ($moduleLoader instanceof ModuleLoader) {
			$this->moduleLoader = $moduleLoader;
			$this->moduleLoader->load();
		} else {
			throw new \Exception('no ModuleLoader found!');
		}
	}

	/**
	 * 启动App.
	 *
	 * @return App
	 */
	public static function start() {
		if (!self::$app) {
			self::$app = new App ();
		}
		$debug = App::icfg('debug', DEBUG_WARN);
		if ($debug > 400 || $debug < 0) {
			$debug = DEBUG_WARN;
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
						self::registerUrlGroup($prefix);
					}
				}
				$module->autoBind();
			}
		}

		return self::$app;
	}

	/**
	 * 获取数据库连接实例.
	 *
	 * @param string $name 数据库配置名.
	 *
	 * @return DatabaseConnection
	 * @throws DialectException
	 */
	public static function db($name = 'default') {
		static $dbs = [];
		if ($name instanceof DatabaseConnection) {
			return $name;
		}
		$config = false;
		if (is_array($name)) {
			$tmpname = implode('_', $name);
			if (isset ($dbs [ $tmpname ])) {
				return $dbs [ $tmpname ];
			}
			$config = $name;
			$name   = $tmpname;
		} else if (is_string($name)) {
			if (isset ($dbs [ $name ])) {
				return $dbs [ $name ];
			}
			$config = self::$app->configLoader->loadDatabaseConfig($name);
		}
		if ($config) {
			$dialect = DatabaseDialect::getDialect($config);
			if ($dialect) {
				$db            = new DatabaseConnection ($dialect);
				$dbs [ $name ] = $db;

				return $db;
			}
		}

		return null;
	}

	/**
	 * 快速获取一个简单表.
	 *
	 * @param string                    $table 表名.
	 * @param string|DatabaseConnection $db    表所在的数据库.
	 *
	 * @return \wulaphp\db\SimpleTable
	 */
	public static function table($table, $db = 'default') {
		return new SimpleTable($table, $db);
	}

	/**
	 * 获取配置.
	 *
	 * @param string $config 配置组.
	 *
	 * @return \wulaphp\conf\Configuration
	 */
	public static function config($config) {
		$app = self::$app;
		if (isset ($app->configs [ $config ])) {
			$confObj = $app->configs [ $config ];
		} else {
			$confObj = $app->configLoader->loadConfig($config);
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
	 * @param string $name    配置项名与组名,例abc@def表示从def配置中读取abc,如果不指定组,则默认为default.
	 * @param int    $default 默认值为0.
	 *
	 * @return int
	 */
	public static function icfg($name, $default = 0) {

		if ($name == null) {
			return 0;
		} else {
			$val = App::cfg($name, $default);

			return intval($val);
		}
	}

	/**
	 * 注册模块.
	 *
	 * @param Module $module
	 *
	 * @throws \Exception
	 */
	public static function register(Module $module) {
		$name = $module->getNamespace();
		if ($name == 'wulaphp') {
			throw new \Exception('the namespace of ' . $module->clzName . ' cannot be wulaphp!');
		}
		if (!preg_match('/^[a-z][a-z_\d]+(\\\\[a-z][a-z_\d]+)*$/i', $name)) {
			throw new \Exception('the namespace "' . $name . '" of ' . $module->clzName . ' is invalide.');
		}

		$dir = $module->getDirname();
		if ($dir != $name) {
			self::$maps ['dir2id'] [ $dir ]  = $name;
			self::$maps ['id2dir'] [ $name ] = $dir;
		}
		self::$modules [ $name ] = $module;
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
	 * @param string $module
	 *
	 * @return Module
	 */
	public static function getModule($module) {
		$info = null;
		if (isset (self::$modules [ $module ])) {
			return self::$modules [ $module ];
		}

		return $info;
	}

	/**
	 * 根据目录名查找模块id(namespace)
	 *
	 * @param string $dir
	 * @param bool   $check 如果为true,模块未加载或不存在时返回null.
	 *
	 * @return null
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
	 * 根据模块ID（namespace）查找模块所在目录.
	 *
	 * @param string $id 模块id
	 *
	 * @return string
	 */
	public static function id2dir($id) {
		if (isset (self::$maps ['id2dir'] [ $id ])) {
			return self::$maps ['id2dir'] [ $id ];
		} else {
			return $id;
		}
	}

	/**
	 * @param array $prefix
	 *
	 */
	private static function registerUrlGroup(array $prefix) {
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
			self::$prefix['check'][ $p ] = 1;
		} else {
			throw new \InvalidArgumentException('prefix is invalid');
		}
	}

	/**
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public static function checkUrlPrefix($prefix) {
		return isset(self::$prefix['check'][ $prefix ]);
	}

	/**
	 * 生成模块url.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function url($url, $replace = true) {
		$url = ltrim($url, '/');

		$urls = explode('/', $url);
		if ($replace) {
			$urls[0] = App::id2dir($urls[0]);
		}
		if (self::$prefix) {
			$urls[0] = str_replace(self::$prefix['char'], self::$prefix['prefix'], $urls[0]);
		}

		return WWWROOT_DIR . implode('/', $urls);
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
			$clzs = explode('\\', $clz);

			$ctrClz = array_pop($clzs);
			array_pop($clzs);

			$id = implode('\\', $clzs);

			$path = App::id2dir($id);
			if (!is_file(MODULES_PATH . $path . '/controllers/' . $ctrClz . '.php')) {
				return '#';
			} else {
				include_once MODULES_PATH . $path . '/controllers/' . $ctrClz . '.php';
				if (!class_exists($clz) || !is_subclass_of($clz, 'wulaphp\mvc\controller\Controller')) {
					return '#';
				}
			}

			$ctr = preg_replace('#Controller$#', '', $ctrClz);

			$ctr = Router::addSlash($ctr);

			if ('index' != $ctr) {
				$path .= '/' . $ctr;
			}

			$prefix = '';
			if (method_exists($clz, 'urlGroup')) {
				$tprefix = ObjectCaller::callClzMethod($clz, 'urlGroup');
				if ($tprefix && isset($tprefix[0])) {
					$prefix = $tprefix[0];
				}
			}

			$prefixes[ $clz ] = $prefix . $path;
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
	 *
	 * @return string
	 */
	public static function res($res) {
		$url     = ltrim($res, '/');
		$urls    = explode('/', $url);
		$urls[0] = App::id2dir($urls[0]);

		return WWWROOT_DIR . MODULE_DIR . '/' . implode('/', $urls);
	}

	/**
	 * 全站资源URL.
	 *
	 * @param string $res
	 *
	 * @return string
	 */
	public static function assets($res) {
		$url = ltrim($res, '/');

		return WWWROOT_DIR . $url;
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
	 * 启动APP.
	 */
	public static function run() {
		if (!isset ($_SERVER ['REQUEST_URI'])) {
			throw new \Exception('Your web server did not provide REQUEST_URI, stop route request.');
		}
		if (!self::$app->router) {
			self::$app->router = Router::getRouter();
		}

		if (WWWROOT_DIR != '/') {
			$uri = substr($_SERVER ['REQUEST_URI'], strlen(WWWROOT_DIR) - 1);
		} else {
			$uri = $_SERVER ['REQUEST_URI'];
		}
		self::$app->router->route($uri);
	}
}