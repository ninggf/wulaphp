<?php

namespace wulaphp\app;

use wulaphp\util\Annotation;

abstract class Module {
	public    $clzName;
	public    $reflection;
	public    $enabled = false;
	protected $namespace;
	protected $path;
	protected $dirname;
	protected $bound   = false;
	protected $currentVersion;

	public function __construct() {
		$this->reflection = $ref = new \ReflectionObject($this);

		$this->clzName = get_class($this);
		$ns            = explode('\\', $this->clzName);
		array_pop($ns);
		$this->namespace      = implode('\\', $ns);
		$this->path           = dirname($ref->getFileName());
		$this->dirname        = basename($this->path);
		$vs                   = $this->getVersionList();
		$this->currentVersion = array_pop(array_keys($vs));
		unset($ns);
	}

	public final function getNamespace() {
		return $this->namespace;
	}

	public final function getPath() {
		return $this->path;
	}

	public final function getDirname() {
		return $this->dirname;
	}

	/**
	 * 当前版本.
	 *
	 * @return string
	 */
	public final function getCurrentVersion() {
		return $this->currentVersion;
	}

	/**
	 * 已经安装版本.
	 *
	 * @return string
	 */
	public function getInstalledVersion() {
		return $this->currentVersion;
	}

	/**
	 * 版本列表.
	 *
	 * @return array
	 */
	protected function getVersionList() {
		$v ['1.0.0'] = 0;

		return $v;
	}

	public final function autoBind() {
		if ($this->bound) {
			return;
		}
		$this->bound = true;
		$ms          = $this->reflection->getMethods(\ReflectionMethod::IS_STATIC);
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
	 * @return string
	 */
	public function getAuthor() {
		return 'wulacms team';
	}

	/**
	 * @return string
	 */
	public abstract function getName();

	/**
	 * @return string
	 */
	public abstract function getDescription();

	/**
	 * @return string
	 */
	public abstract function getHomePageURL();
}