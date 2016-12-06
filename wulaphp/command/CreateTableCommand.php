<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\db\DialectException;

class CreateTableCommand extends ArtisanCommand {
	private $maps = ['tinyint' => 'int', 'smallint' => 'int', 'int' => 'int', 'mediumint' => 'int'];

	public function cmd() {
		return 'create-table';
	}

	public function desc() {
		return 'create table class based on the table in database.';
	}

	protected function execute($options) {
		throw new \Exception('asdfasdf');
		$table = $this->opt();
		if (!$table) {
			$this->help('miss <table> name');

			return 1;
		}

		$namespace = App::dir2id($options['m'], true);
		if (!$namespace) {
			$this->log('ERROR: the module "' . $options['m'] . '" was not found!');

			return 1;
		}

		$namespace  = $namespace . '\models;';
		$modulePath = MODULES_PATH . $options['m'] . DS;
		if (!is_dir($modulePath . 'models') && !@mkdir($modulePath . 'models')) {
			$this->log('ERROR: cannot create  "' . $options['m'] . '/models " directory!');

			return 1;
		}
		$tableCls = str_replace('_', '', ucwords($table, '_'));
		if (is_file($modulePath . 'models/' . $tableCls . 'Table.php')) {
			$this->log($modulePath . 'models/' . $tableCls . 'Table.php is exist!');

			return 0;
		}

		try {
			$desc = App::db()->query("show full columns from `{$table}`");
			if (!$desc) {
				$this->log('the table "' . $table . '" is not exist');

				return 1;
			}
			$fields = [];
			foreach ($desc as $field) {
				$def   = [''];
				$def[] = '/**';
				if ($field['Comment']) {
					$def[] = ' * ' . $field['Comment'];
					$def[] = ' * ';
				}
				$def[] = ' * @var ' . $this->getType($field['Type']);
				if ($field['Null'] == 'NO') {
					$def[] = ' * @required ';
				}
				$def[]    = ' */';
				$def[]    = 'public $' . $field['Field'] . (isset($field['Default']) ? ' = \'' . $field['Default'] . "'" : '') . ';';
				$fields[] = implode("\n    ", $def);
			}
			$fieldString = implode("\n", $fields);

			$bootstrap = file_get_contents(__DIR__ . '/tpl/table.tpl');
			$bootstrap = str_replace(['{$namespace}', '{$table}', '{$fields}'], [$namespace, $tableCls, $fieldString], $bootstrap);

			file_put_contents($modulePath . 'models/' . $tableCls . 'Table.php', $bootstrap);
			$this->log(wordwrap('the table class ' . $tableCls . 'Table created successfully in ' . $modulePath . 'models/' . $tableCls . 'Table.php', 72));
		} catch (DialectException $e) {
			$this->log('ERROR: ' . $e->getMessage());

			return 1;
		}

		return 0;
	}

	protected function getOpts() {
		return ['m:module' => 'which module the Table class belongs to.'];
	}

	protected function argDesc() {
		return '<table>';
	}

	private function getType($type) {
		$type = strtolower(preg_replace('#^([a-z]+)(\s*\(.*)?#', '\1', $type));
		if (isset($this->maps[ $type ])) {
			return $this->maps[ $type ];
		}

		return 'string';
	}
}