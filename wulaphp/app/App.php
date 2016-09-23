<?php
namespace wulaphp\app;

use wulaphp\conf\Configuration;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\router\Router;

/**
 * 应用管理器.
 *
 * @author leo
 *
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
	private static $maps    = array('dir2id' => array(), 'id2dir' => array());
	private static $modules = array();
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
			trigger_error('no ConfigurationLoader found!', E_USER_ERROR);
		}
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
			trigger_error('no ExtensionLoader found!', E_USER_ERROR);
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
			trigger_error('no ModuleLoader found!', E_USER_ERROR);
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
		$coreCfg = App::cfg(null);
		$debug   = intval($coreCfg->get('debug', DEBUG_WARN));
		if ($debug < 1 || $debug > 5) {
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
		$timezone = $coreCfg->get('timezone', 'Asia/Shanghai');
		// 时区设置
		define('TIMEZONE', $timezone);
		date_default_timezone_set(TIMEZONE);
		define('BASE_URL', Router::detect(true));
		define('CONTEXT_URL', Router::detect());

		return self::$app;
	}

	/**
	 * 获取数据库连接实例.
	 *
	 * @param string $name 数据库配置名.
	 *
	 * @return DatabaseConnection {@link DatabaseConnection}
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
		trigger_error('cannot connect to the database', E_USER_ERROR);

		return null;
	}

	/**
	 * 读取配置.
	 *
	 * @param string $name   配置项名.
	 * @param string $config 配置.
	 *
	 * @return mixed 配置值.
	 */
	public static function cfg($name = null, $config = 'default') {
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

		if ($name == null) {
			return $confObj;
		} else {
			return $confObj->get($name);
		}
	}

	public static function bcfg($name, $config = 'default') {
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

		if ($name == null) {
			return false;
		} else {
			$val = $confObj->get($name);
			if (empty ($val) || $val == 'false' || $val == '0') {
				return false;
			}

			return true;
		}
	}

	public static function icfg($name, $config = 'default') {
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

		if ($name == null) {
			return 0;
		} else {
			$val = $confObj->get($name);

			return intval($val);
		}
	}

	/**
	 * 注册模块.
	 *
	 * @param string $name 模块名,只能是英文字母.
	 * @param string $file
	 */
	public static function register($name, $file = null) {
		$name = strtolower($name);
		if ($name == 'wulaphp') {
			trigger_error('the name of module cannot be wulaphp!', E_USER_ERROR);
		}
		if (!preg_match('/^[a-z]+$/', $name)) {
			trigger_error('the name of module must be made of "a-z"');
		}
		$path = dirname($file);
		$dir  = basename($path);
		if ($dir != $name) {
			self::$maps ['dir2id'] [ $dir ]  = $name;
			self::$maps ['id2dir'] [ $name ] = $dir;
		}
		self::$modules [ $dir ] = $path;
	}

	/**
	 * 获取模块信息.
	 *
	 * @param string $module
	 *
	 * @return array
	 */
	public static function getModule($module) {
		$info = null;
		if (isset (self::$modules [ $module ])) {
			$info ['path'] = self::$modules [ $module ];
			if (isset (self::$maps ['dir2id'] [ $module ])) {
				$info ['namespace'] = self::$maps ['dir2id'] [ $module ];
			} else {
				$info ['namespace'] = $module;
			}
		}

		return $info;
	}

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
	 *
	 * @param $id
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
			if (isset (self::$maps ['id2dir'] [ $module ])) {
				$pkg [0] = self::$maps ['id2dir'] [ $module ];
			}
			$file = implode(DS, $pkg) . '.php';

			return self::$app->moduleLoader->loadClass($module, $file);
		} else {
			return null;
		}
	}

	/**
	 * 启动APP.
	 */
	public static function run() {
		if (!isset ($_SERVER ['REQUEST_URI'])) {
			trigger_error('Your web server did not provide REQUEST_URI, stop route request.', E_USER_ERROR);
		}
		if (!self::$app->router) {
			self::$app->router = new Router (self::$modules);
		}
		self::$app->router->route($_SERVER ['REQUEST_URI']);
	}
}