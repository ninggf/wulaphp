<?php

namespace wulaphp\app;

use wulaphp\util\Annotation;

abstract class Module {
	public    $clzName;
	public    $reflection;
	protected $namespace;
	protected $path;
	protected $dirname;
	protected $currentVersion;
	protected $bound = false;

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

	public function getCurrentVersion() {
		return $this->currentVersion;
	}

	public function getInstalledVersion() {
		return $this->currentVersion;
	}

	public function autoBind() {
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
					}
				}
			}
		}
	}

	public function getVersionList() {
		$v ['1.0.0'] = 0;

		return $v;
	}

	public function getDependences() {
		return null;
	}

	public function install() {
		return true;
	}

	public function upgrade() {
		return true;
	}

	public function getAuthor() {
		return 'wula team';
	}

	public abstract function getName();

	public abstract function getDescription();

	public abstract function getHomePageURL();

}