<?php
namespace wulaphp\app;

use wulaphp\conf\Configuration;
use wulaphp\router\Router;

/**
 * 应用管理器.
 *
 * @author leo
 *
 */
class App {

    private $dbConfigs = array ();

    private $configs = array ();

    private $router;

    private $configLoader;

    private $moduleLoader;

    private static $maps = array (
        'dir2id' => array (),'id2dir' => array ()
    );

    private static $modules = array ();

    private static $app = false;

    private function __construct() {
        /* 加载配置文件 */
        $configLoader = new \extensions\conf\ConfigurationLoader ();
        if ($configLoader instanceof \wulaphp\conf\BaseConfigurationLoader) {
            $configLoader->beforeLoad ();
            $this->configs ['default'] = $configLoader->loadConfig ();
            $configLoader->postLoad ();
            $this->configLoader = $configLoader;
        } else {
            trigger_error ( 'no ConfigurationLoader found!', E_USER_ERROR );
        }
        // 加载模块
        $moduleLoader = new \extensions\app\ModuleLoader ();
        if ($moduleLoader instanceof IModuleLoader) {
            $moduleLoader->load ( $this );
            $this->moduleLoader = $moduleLoader;
        } else {
            trigger_error ( 'no ModuleLoader found!', E_USER_ERROR );
        }
    }

    /**
     * 启动App.
     *
     * @return App
     */
    public static function start() {
        if (! self::$app) {
            self::$app = new App ();
        }
        $coreCfg = App::cfg ( null );
        $debug = intval ( $coreCfg->get ( 'debug', DEBUG_WARN ) );
        if ($debug < 1 || $debug > 5) {
            $debug = DEBUG_WARN;
        }
        define ( 'DEBUG', $debug );
        if (DEBUG == DEBUG_OFF) {
            define ( 'KS_ERROR_REPORT_LEVEL', E_ALL & ~ E_NOTICE & ~ E_DEPRECATED & ~ E_STRICT & ~ E_WARNING );
            @ini_set ( 'display_errors', 0 );
        } else if (DEBUG == DEBUG_DEBUG) {
            define ( 'KS_ERROR_REPORT_LEVEL', E_ALL & ~ E_NOTICE );
            @ini_set ( 'display_errors', 1 );
        } else {
            define ( 'KS_ERROR_REPORT_LEVEL', E_ALL & ~ E_NOTICE & ~ E_DEPRECATED & ~ E_STRICT );
            @ini_set ( 'display_errors', 1 );
        }
        error_reporting ( KS_ERROR_REPORT_LEVEL );
        $debug = intval ( $coreCfg->get ( 'timezone', 'Asia/Shanghai' ) );
        // 时区设置
        define ( 'TIMEZONE', 'Asia/Shanghai' );
        date_default_timezone_set ( TIMEZONE );
        define ( 'BASE_URL', Router::detect ( true ) );
        define ( 'CONTEXT_URL', Router::detect () );
        return self::$app;
    }

    /**
     * 读取配置.
     *
     * @param string $name 配置项名.
     * @param string $config 配置.
     * @return mixed 配置值.
     */
    public static function cfg($name = null, $config = 'default') {
        $app = self::$app;
        if (isset ( $app->configs [$config] )) {
            $confObj = $app->configs [$config];
        } else {
            $confObj = $app->configLoader->loadConfig ( $config );
            if (! $confObj) {
                $confObj = new Configuration ( $config );
            }
            $app->configs [$config] = $confObj;
        }
        
        if ($name == null) {
            return $confObj;
        } else {
            return $confObj->get ( $name );
        }
    }

    public static function bcfg($name, $config = 'default') {
        $app = self::$app;
        if (isset ( $app->configs [$config] )) {
            $confObj = $app->configs [$config];
        } else {
            $confObj = $app->configLoader->loadConfig ( $config );
            if (! $confObj) {
                $confObj = new Configuration ( $config );
            }
            $app->configs [$config] = $confObj;
        }
        
        if ($name == null) {
            return false;
        } else {
            $val = $confObj->get ( $name );
            if (empty ( $val ) || $val == 'false' || $val == '0') {
                return false;
            }
            return true;
        }
    }

    public static function icfg($name, $config = 'default') {
        $app = self::$app;
        if (isset ( $app->configs [$config] )) {
            $confObj = $app->configs [$config];
        } else {
            $confObj = $app->configLoader->loadConfig ( $config );
            if (! $confObj) {
                $confObj = new Configuration ( $config );
            }
            $app->configs [$config] = $confObj;
        }
        
        if ($name == null) {
            return 0;
        } else {
            $val = $confObj->get ( $name );
            return intval ( $val );
        }
    }

    /**
     * 注册模块.
     *
     * @param string $name 模块名,只能是英文字母.
     * @param string $file
     */
    public static function register($name, $file = null) {
        $name = strtolower ( $name );
        if ($name == 'phpeffi') {
            trigger_error ( 'the name of module cannot be phpeffi!', E_USER_ERROR );
        }
        if (! preg_match ( '/^[a-z]+$/', $name )) {
            trigger_error ( 'the name of module must be made of "a-z"' );
        }
        $path = dirname ( $file );
        $dir = basename ( $path );
        if ($dir != $name) {
            self::$maps ['dir2id'] [$dir] = $name;
            self::$maps ['id2dir'] [$name] = $dir;
        }
        self::$modules [$dir] = $path;
    }

    /**
     * 加载模块中定义的类.
     *
     * @param string $cls
     * @return mixed 类文件全路径.
     */
    public static function loadClass($cls) {
        $pkg = explode ( '\\', $cls );
        if (count ( $pkg ) > 1) {
            $module = $pkg [0];
            if (isset ( self::$maps ['id2dir'] [$module] )) {
                $pkg [0] = self::$maps ['id2dir'] [$module];
            }
            $file = implode ( '/', $pkg ) . '.php';
            return self::$app->moduleLoader->loadClass ( $module, $file );
        } else {
            return null;
        }
    }

    public static function route() {
        if (! isset ( $_SERVER ['REQUEST_URI'] )) {
            trigger_error ( 'Your web server did not provide REQUEST_URI, stop route request.', E_USER_ERROR );
        }
        if (! self::$app->router) {
            self::$app->router = new \wulaphp\router\Router ( self::$modules );
        }
        if (isset ( $_SERVER ['REQUEST_URI'] )) {
            self::$app->router->route ( $_SERVER ['REQUEST_URI'] );
        } else if (isset ( $_SERVER ['PATH_INFO'] )) {
            self::$app->router->route ( $_SERVER ['PATH_INFO'] );
        } else {
            trigger_error ( 'REQUEST_URI and PATH_INFO are missing!', E_USER_ERROR );
            self::$app->router->route ( 'index.html' );
        }
    }
}