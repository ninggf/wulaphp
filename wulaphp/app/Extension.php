<?php

namespace wulaphp\app;

use wulaphp\i18n\I18n;
use wulaphp\util\Annotation;

abstract class Extension {
    public    $clzName;
    public    $reflection;
    protected $currentVersion;
    protected $bound = false;
    protected $path;

    public function __construct() {
        $this->reflection     = $ref = new \ReflectionObject($this);
        $this->path           = dirname($ref->getFileName());
        $this->clzName        = get_class($this);
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

        // 批量绑定
        $this->bind();
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
                    }
                }
            }
        }
    }

    public function getVersionList() {
        $v ['1.0.0'] = 0;

        return $v;
    }

    /**
     * 批量事件处理器注册.
     */
    protected function bind() {
    }

    public abstract function getName();

    public abstract function getDescription();

    public abstract function getHomePageURL();
}