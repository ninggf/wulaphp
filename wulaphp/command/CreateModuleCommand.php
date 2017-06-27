<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;

class CreateModuleCommand extends ArtisanCommand {
	public function cmd() {
		return 'create:module';
	}

	public function desc() {
		return 'create module structure for your';
	}

	protected function execute($options) {
		$rtn = 0;
		$dir = $this->opt();
		if (!$dir) {
			$this->help('missing <module> name');

			return 1;
		}
		$namespace = isset($options['n']) ? $options['n'] : $dir;

		if (!preg_match('#^[a-z][a-z_\-\d]*$#', $dir)) {
			$this->error('illegal module name: ' . $this->color->str($dir, 'white', 'red'));

			return 1;
		}

		if (!preg_match('#^[a-z][a-z_\d]+(\\\\[a-z][a-z_\-\d]+)*$#', $namespace)) {
			$this->error('illegal namespace: ' . $this->color->str($namespace, 'white', 'red'));

			return 1;
		}

		$modulePath = MODULES_PATH . $dir . DS;
		if (file_exists($modulePath)) {
			$this->error('the directory ' . $this->color->str($dir, 'white', 'red') . ' is exist.');

			return 1;
		}
		$module = App::getModule($namespace);
		if ($module) {
			$this->error('the namespace ' . $this->color->str($namespace, 'white', 'red') . ' of a module is exist.');

			return 1;
		}
		while (true) {
			//创建目录

			if (!mkdir($modulePath)) {
				$this->error('cannot create dir for module: ' . $modulePath);
				$rtn = 1;
				break;
			}

			mkdir($modulePath . 'controllers');
			mkdir($modulePath . 'views');
			mkdir($modulePath . 'views' . DS . 'index');
			mkdir($modulePath . 'classes');
			mkdir($modulePath . 'models');
			mkdir($modulePath . 'test');
			$ns     = explode('\\', $namespace);
			$module = ucfirst($ns[0]);
			// 创建引导文件
			$bootstrap = file_get_contents(__DIR__ . '/tpl/bootstrap.tpl');
			$bootstrap = str_replace(['{$namespace}', '{$module}'], [$namespace, $module], $bootstrap);
			file_put_contents($modulePath . 'bootstrap.php', $bootstrap);

			// 创建默认控制器.
			$bootstrap = file_get_contents(__DIR__ . '/tpl/controller.tpl');
			$bootstrap = str_replace(['{$namespace}', '{$module}'], [$namespace, 'Index'], $bootstrap);
			file_put_contents($modulePath . 'controllers/IndexController.php', $bootstrap);

			//视图
			$bootstrap = file_get_contents(__DIR__ . '/tpl/index.tpl');
			file_put_contents($modulePath . 'views/index/index.tpl', $bootstrap);
			// composer.json
			if (isset($options['c'])) {
				$bootstrap = file_get_contents(__DIR__ . '/tpl/composer.json');
				$bootstrap = str_replace(['{$name}', '{$type}'], ['wula/' . $dir, 'module'], $bootstrap);
				file_put_contents($modulePath . 'composer.json', $bootstrap);
			}

			// 测试
			$phpunit = file_get_contents(__DIR__ . '/tpl/phpunit.xml');
			$phpunit = str_replace('{$module}', $namespace, $phpunit);
			file_put_contents($modulePath . 'phpunit.xml', $phpunit);

			// 添加.gitattributes
			file_put_contents($modulePath . '.gitattributes', "test/ export-ignore\nphpunit.xml export-ignore\n");
			file_put_contents($modulePath . 'schema.sql.php', '');
			$this->success('The module ' . $this->color->str($dir, 'blue') . ' is created successfully.');
			break;
		}

		return $rtn;
	}

	protected function getOpts() {
		return ['n::namespace' => 'the namespace of the module', 'c' => 'create composer.json for module'];
	}

	protected function argDesc() {
		return '<module>';
	}
}