<?php

namespace wulaphp\app;

abstract class Extension {
    public         $clzName;
    public         $reflection;
    protected      $currentVersion;
    protected      $bound = false;
    protected      $path;
    protected      $namespace;
    private static $hooks = [];

    public function __construct() {
        $this->reflection = $ref = new \ReflectionObject($this);
        $this->path       = dirname($ref->getFileName());
        $this->clzName    = get_class($this);
        $ns               = explode('\\', $this->clzName);
        array_pop($ns);
        $this->namespace      = implode('\\', $ns);
        $vs                   = $this->getVersionList();
        $this->currentVersion = array_pop(array_keys($vs));
    }

    public function getCurrentVersion() {
        return $this->currentVersion;
    }

    /**
     * @param string|null $file 文件名.
     *
     * @return string 路径
     */
    public final function getPath($file = null) {
        return $this->path . ($file ? DS . $file : '');
    }

    /**
     * 加载模块内文件内容.
     *
     * @param string $file
     *
     * @return bool|string
     */
    public final function loadFile($file) {
        $f = $this->getPath($file);
        if (is_file($f) && is_readable($f)) {
            return @file_get_contents($f);
        }

        return false;
    }

    public final function autoBind() {
        if ($this->bound) {
            return;
        }
        $this->bound = true;
        if (is_dir($this->path . DS . 'hooks')) {
            self::$hooks[] = $this->namespace . '\\hooks\\';
        }
    }

    public function getVersionList() {
        $v ['1.0.0'] = 0;

        return $v;
    }

    public final static function getHooks() {
        return self::$hooks;
    }

    public function getDescription() {
        return '';
    }

    public function getHomePageURL() {
        return '';
    }

    public abstract function getName();
}