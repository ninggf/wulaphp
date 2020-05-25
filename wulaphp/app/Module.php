<?php

namespace wulaphp\app;

use wulaphp\db\DatabaseConnection;
use wulaphp\util\Annotation;

/**
 * 模块基类。所有模块都必须在引导文件`bootstrap.php`中实现一个模块类继承自它，并将其实例注册到框架。
 *
 * @package wulaphp\app
 */
abstract class Module {
    /**@var \ReflectionObject */
    public $reflection;
    public $clzName;
    public $enabled          = false;
    public $installed        = false;
    public $upgradable       = false;
    public $installedVersion = '0.0.0';
    public $group            = '';
    public $hasHooks         = false;
    public $hookPath         = null;
    public $isKernel         = true;

    protected $namespace;
    protected $path;
    protected $dirname;
    protected $currentVersion;

    private $subEnabled = false;
    private $bound      = false;

    /**
     * Module constructor.
     */
    public function __construct() {
        $this->reflection = $ref = new \ReflectionObject($this);
        $this->clzName    = get_class($this);
        $ns               = explode('\\', $this->clzName);
        array_pop($ns);
        $ann                    = new Annotation($this->reflection);
        $this->group            = $ann->getString('group', 'Unknown');
        $this->subEnabled       = $ann->has('subEnabled');
        $this->namespace        = implode('\\', $ns);
        $this->path             = dirname($ref->getFileName());
        $this->hookPath         = $this->path . DS . 'hooks' . DS;
        $this->hasHooks         = is_dir($this->hookPath);
        $this->dirname          = basename($this->path);
        $vs                     = array_keys($this->getVersionList());
        $this->currentVersion   = array_pop($vs);
        $this->installedVersion = $this->currentVersion;
    }

    /**
     * 获取模块的命名空间.
     *
     * @return string 命名空间.
     */
    public final function getNamespace(): string {
        return $this->namespace;
    }

    /**
     * 获取模块里的路径。
     *
     * @param string|null $file     文件名.
     * @param bool        $absolute 是否是绝对路径
     *
     * @return string 路径
     */
    public final function getPath(?string $file = null, bool $absolute = true): string {
        if ($absolute) {
            return $this->path . ($file ? DS . $file : '');
        }

        return MODULE_DIR . DS . $this->dirname . DS . ($file ? $file : '');
    }

    /**
     * 获取相对路径
     *
     * @param null|string $file
     *
     * @return string
     */
    public final function getRelativePath(?string $file = null): string {
        return $file ? MODULE_DIR . DS . $this->dirname . DS . ($file ? $file : '') : $this->path . DS;
    }

    /**
     * 加载模块内文件内容.
     *
     * @param string $file
     *
     * @return string
     */
    public final function loadFile(string $file): ?string {
        $f = $this->getPath($file);
        if (is_file($f) && is_readable($f)) {
            $cnt = file_get_contents($f);

            return $cnt === false ? null : $cnt;
        }

        return null;
    }

    /**
     * 模块目录名。
     *
     * @return string 目录名
     */
    public final function getDirname(): string {
        return $this->dirname;
    }

    /**
     * 当前版本.
     *
     * @return string
     */
    public final function getCurrentVersion(): string {
        return $this->currentVersion;
    }

    /**
     * 注册事件处理器.
     * @throws
     */
    public function autoBind() {
        if ($this->bound) {
            return;
        }
        $this->bound = true;
        // 批量绑定
        $hooks = $this->bind();
        if ($hooks && is_array($hooks)) {
            foreach ($hooks as $hook => $impl) {
                if (is_array($impl)) {
                    [$func, $argc, $priority] = array_pad($impl, 3, null);
                    bind($hook, $func, $priority ? $priority : 10, $argc ? $argc : 1);
                } else {
                    bind($hook, $impl);
                }
            }
        }
        // 根据注解进行绑定
        $ms = $this->reflection->getMethods(\ReflectionMethod::IS_STATIC);
        foreach ($ms as $m) {
            if (!$m->isPublic()) {
                continue;
            }
            $annotation = new Annotation($m);
            $bind       = $annotation->getArray('bind');
            if ($bind) {
                $name     = $m->getName();
                $argc     = $m->getNumberOfParameters();
                $priority = isset($bind[1]) ? intval($bind[1]) : 10;
                bind($bind[0], [$this->clzName, $name], $priority, $argc);
            } else {
                $filter = $annotation->getArray('filter');
                if ($filter) {
                    $name = $m->getName();
                    $argc = $m->getNumberOfParameters();
                    if ($argc > 0) {
                        $priority = isset($filter[1]) ? intval($filter[1]) : 10;
                        bind($filter[0], [$this->clzName, $name], $priority, $argc);
                    } else {
                        throw_exception('the method ' . $name . ' of ' . $this->clzName . ' must at least have one parameter.');
                    }
                }
            }
        }
    }

    /**
     * 自定义勾子绑定
     *
     * @return array|null
     */
    protected function bind(): ?array {
        return [];
    }

    /**
     * 模块菜单定义数据.
     *
     * @return array
     */
    public function menu(): array {
        return [];
    }

    /**
     * 模块访问权限定义数据.
     *
     * @return array
     */
    public function acl(): array {
        return [];
    }

    /**
     * 版本列表.
     *
     * @return array
     */
    public function getVersionList() {
        $v ['1.0.0'] = '第一个版本';

        return $v;
    }

    /**
     * 模块信息.
     *
     * @return array
     */
    public function info(): array {
        $info = get_object_vars($this);
        unset($info['reflection'], $info['clzName'], $info['bound']);
        $info['name']   = $this->getName();
        $info['author'] = $this->getAuthor();
        $info['desc']   = $this->getDescription();
        $info['home']   = $this->getHomePageURL();
        $info['ver']    = $info['currentVersion'];
        $info['cver']   = $info['installedVersion'];
        unset($info['currentVersion'], $info['installedVersion']);

        if ($this->installed) {
            if ($this->upgradable && $this->enabled) {
                $info['status'] = 2;
            } else if ($this->enabled) {
                $info['status'] = 1;
            } else {
                $info['status'] = 0;
            }
        } else {
            $info['status'] = - 1;
        }

        return $info;
    }

    /**
     * 作者。
     *
     * @return string
     */
    public function getAuthor() {
        return 'wulacms team';
    }

    /**
     * 依赖.
     * @return array|null
     * @deprecated 使用composer.json定义
     */
    public function getDependences() {
        return null;
    }

    /**
     * 安装.
     *
     * @param DatabaseConnection $con
     * @param int                $kernel 1代表安装的是内核模块.
     *
     * @return bool
     */
    public function install(DatabaseConnection $con, int $kernel = 0) {
        return true;
    }

    /**
     * 卸载.
     * @return bool
     */
    public function uninstall() {
        return true;
    }

    /**
     * 停用
     * @return bool
     */
    public function stop() {
        return true;
    }

    /**
     * 启用
     * @return bool
     */
    public function start() {
        return true;
    }

    /**
     * 升级
     *
     * @param DatabaseConnection $db
     * @param string             $toVer
     * @param string             $fromVer
     *
     * @return bool
     */
    public function upgrade($db, $toVer, $fromVer = '0.0.0') {
        return true;
    }

    /**
     * 检测环境.
     *
     * @param array $envs
     */
    public function envCheck(&$envs) {

    }

    /**
     * 取当前模板所定义的表.
     *
     * @param \wulaphp\db\dialect\DatabaseDialect $dialect
     *
     * @return array 模块定义的表视图
     */
    public function getDefinedTables($dialect) {
        return [];
    }

    /**
     * 是否有子模块
     *
     * @return bool
     */
    public function hasSubModule() {
        return $this->subEnabled;
    }

    /**
     * 检测文件权限.
     *
     * @param string $f 文件路径.
     * @param bool   $r 读
     * @param bool   $w 写
     *
     * @return array ['required'=>'','checked'=>'','pass'=>'']
     */
    public final static function checkFile($f, $r = true, $w = true) {
        $rst     = [];
        $checked = $required = '';
        if ($r) {
            $required .= '可读';
        }
        if ($w) {
            $required .= '可写';
        }
        if (file_exists($f)) {
            if ($r) {
                $checked = is_readable($f) ? '可读' : '不可读';
            }
            if ($w) {
                if (is_dir($f)) {
                    $len = @file_put_contents($f . '/test.dat', 'test');
                    if ($len > 0) {
                        @unlink($f . '/test.dat');
                        $checked .= '可写';
                    } else {
                        $checked .= '不可写';
                    }
                } else {
                    $checked .= is_writable($f) ? '可写' : '不可写';
                }
            }
        } else {
            $checked = '不存在';
        }
        $rst ['required'] = $required;
        $rst ['checked']  = $checked;
        $rst ['pass']     = $checked == $required;
        $rst ['optional'] = false;

        return $rst;
    }

    /**
     * 检测ini配置是否开启.
     *
     * @param string $key
     * @param int    $r
     * @param bool   $optional
     *
     * @return array ['required'=>'','checked'=>'','pass'=>'']
     */
    public final static function checkEnv($key, $r, $optional = false) {
        $rst = [];
        $rel = strtolower(ini_get($key));
        $rel = ($rel == '0' || $rel == 'off' || $rel == '') ? 0 : 1;
        if ($rel == $r) {
            $rst['pass'] = true;
        } else {
            $rst['pass'] = false;
        }
        $rst['required'] = $r ? '开' : '关';
        $rst['checked']  = $rel ? '开' : '关';
        $rst['optional'] = $optional;

        return $rst;
    }

    /**
     * 模块名
     * @return string
     */
    public abstract function getName();

    /**
     * 模块描述.
     * @return string
     */
    public function getDescription() {
        return '';
    }

    /**
     * 模块主页
     * @return string
     */
    public function getHomePageURL() {
        return '';
    }
}