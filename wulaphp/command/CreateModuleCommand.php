<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class CreateModuleCommand extends ArtisanCommand {
	public function cmd() {
		return 'create-module';
	}

	public function desc() {
		return 'create module structure for your';
	}

	protected function execute($options) {
		$rtn = 0;
		$dir = $this->opt();
		if (!$dir) {
			$this->help('miss <module> name');

			return 1;
		}
		$namespace = isset($options['n']) ? $options['n'] : $dir;
		if (!preg_match('#^[a-z][a-z_\-\d]*$#', $dir)) {
			$this->log('ERROR: illegal module name: ' . $dir);

			return 1;
		}
		if (!preg_match('#^[a-z][a-z_\d]*$#', $namespace)) {
			$this->log('ERROR: illegal namespace: ' . $namespace);

			return 1;
		}
		$modulePath = MODULES_PATH . $dir . DS;
		if (file_exists($modulePath)) {
			$this->log('ERROR: the module ' . $dir . ' is exist.');

			return 1;
		}
		while (true) {
			//创建目录

			if (!mkdir($modulePath)) {
				$this->log('ERROR: cannot create dir for module: ' . $modulePath);
				$rtn = 1;
				break;
			}

			mkdir($modulePath . 'controllers');
			mkdir($modulePath . 'views');
			mkdir($modulePath . 'classes');
			mkdir($modulePath . 'models');

			$module = ucfirst($namespace);
			// 创建引导文件
			$bootstrap = file_get_contents(__DIR__ . '/tpl/bootstrap.tpl');
			$bootstrap = str_replace(['{$namespace}', '{$module}'], [$namespace, $module], $bootstrap);
			file_put_contents($modulePath . 'bootstrap.php', $bootstrap);

			// 创建默认控制器.
			$bootstrap = file_get_contents(__DIR__ . '/tpl/controller.tpl');
			$bootstrap = str_replace(['{$namespace}', '{$module}'], [$namespace, $module], $bootstrap);
			file_put_contents($modulePath . 'controllers/' . $module . 'Controller.php', $bootstrap);

			//视图
			$bootstrap = file_get_contents(__DIR__ . '/tpl/index.tpl');
			file_put_contents($modulePath . 'views/index.tpl', $bootstrap);

			$this->log('module ' . $dir . ' created successfully.');
			break;
		}

		return $rtn;
	}

	protected function getOpts() {
		return ['n::namespace' => 'the namespace of the module'];
	}

	protected function argDesc() {
		return '<module>';
	}
}