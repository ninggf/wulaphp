<?php

namespace wulaphp\app;

use wulaphp\util\Annotation;

abstract class Extension {
	public    $clzName;
	public    $reflection;
	protected $currentVersion;
	protected $bound = false;

	public function __construct() {
		$this->reflection = $ref = new \ReflectionObject($this);

		$this->clzName = get_class($this);
		$ns            = explode('\\', $this->clzName);
		if (count($ns) != 2) {
			throw new \Exception('the namespace of ' . $this->clzName . ' is not allowed.');
		}
		$vs                   = $this->getVersionList();
		$this->currentVersion = array_pop(array_keys($vs));
		unset($ns, $ms);
	}

	public function getCurrentVersion() {
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

	public abstract function getName();

	public abstract function getDescription();

	public abstract function getHomePageURL();
}