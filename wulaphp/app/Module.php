<?php

namespace wulaphp\app;

use wulaphp\i18n\I18n;
use wulaphp\util\Annotation;

/**
 * 模块基类.
 *
 * @package wulaphp\app
 */
abstract class Module {
	public    $clzName;
	public    $reflection;
	public    $enabled          = false;
	public    $installed        = false;
	public    $upgradable       = false;
	public    $installedVersion = '0.0.0';
	public    $group            = '';
	protected $namespace;
	protected $path;
	protected $dirname;
	protected $bound            = false;
	protected $currentVersion;

	public function __construct() {
		$this->reflection = $ref = new \ReflectionObject($this);

		$this->clzName = get_class($this);
		$ns            = explode('\\', $this->clzName);
		$ann           = new Annotation($this->reflection);
		$this->group   = $ann->getString('group');
		array_pop($ns);
		$this->namespace      = implode('\\', $ns);
		$this->path           = dirname($ref->getFileName());
		$this->dirname        = basename($this->path);
		$vs                   = $this->getVersionList();
		$keys                 = array_keys($vs);
		$this->currentVersion = array_pop($keys);
		unset($ns);
	}

	/**
	 * @return string 命名空间.
	 */
	public final function getNamespace() {
		return $this->namespace;
	}

	/**
	 * @param string $file 文件名.
	 *
	 * @return string 路径
	 */
	public final function getPath($file = null) {
		return $this->path . ($file ? DS . $file : '');
	}

	/**
	 * @return string 目录名
	 */
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
	 * 版本列表.
	 *
	 * @return array
	 */
	public function getVersionList() {
		$v ['1.0.0'] = '第一个版本';

		return $v;
	}

	/**
	 * 注册事件处理器.
	 */
	public final function autoBind() {
		if ($this->bound) {
			return;
		}
		$this->bound = true;
		//加载语言
		if (is_dir($this->path . DS . 'lang')) {
			I18n::addLang($this->path . DS . 'lang');
		}
		// 批量绑定
		$this->bind();
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
	 * 模块信息.
	 *
	 * @return array
	 */
	public function info() {
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
			$info['status'] = -1;
		}

		return $info;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return 'wulacms team';
	}

	/**
	 * 批量事件处理器注册.
	 */
	protected function bind() {

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