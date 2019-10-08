<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;

class CreModelCommand extends ArtisanCommand {
    public function cmd() {
        return 'model';
    }

    public function desc() {
        return 'Create a Model Class';
    }

    public function argDesc() {
        return '<module> <tableName>';
    }

    public function execute($options) {
        $module = $this->opt(1);
        $ctr    = $this->opt(2);
        if (!$module) {
            $this->error("give me a module to which the model belongs!");

            return 1;
        }
        $module = trim($module, '/');
        if (strpos($module, '/')) {
            $ms        = explode('/', $module);
            $module    = $ms[0];
            $subModule = $ms[1];
        } else {
            $subModule = false;
        }

        if (!($m = App::getModule($module))) {
            $this->error("the '$module' module is not found!");

            return 1;
        }
        if (!preg_match('/^[a-z][\w\d_]+$/i', $ctr)) {
            $this->error("'$ctr' is an illegal table name");

            return 1;
        }
        $clz = str_replace('_', '', ucwords($ctr, '_'));
        if ($subModule) {
            $path      = $m->getPath($subModule . DS . 'model');
            $namespace = $m->getNamespace() . '\\' . $subModule;
        } else {
            $path      = $m->getPath('model');
            $namespace = $m->getNamespace();
        }
        $file = $path . DS . $clz . 'Model.php';

        if (is_file($file)) {
            $this->error('model: "' . $clz . '" exists');

            return 1;
        }

        if (!is_dir($path) && !@mkdir($path, 0755, true)) {
            $this->error('Cannot create model dir: ' . $path);

            return 1;
        }

        $bootstrap = file_get_contents(__DIR__ . '/tpl/model.tpl');
        $bootstrap = str_replace(['{$namespace}', '{$clz}'], [$namespace, $clz], $bootstrap);
        if (!@file_put_contents($file, $bootstrap)) {
            $this->error('Cannot create model: ' . $file);
        }
        $this->output("'$clz' model created successfully");

        return 0;
    }
}