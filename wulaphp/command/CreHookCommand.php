<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;

/**
 * Class CreHookCommand
 * @package wulaphp\command
 * @internal
 */
class CreHookCommand extends ArtisanCommand {
    public function cmd() {
        return 'hook';
    }

    public function desc() {
        return 'Create a Hook Handler Class';
    }

    public function argDesc() {
        return '<module> <hook>';
    }

    public function getOpts() {
        return ['a' => 'alter'];
    }

    public function execute($options) {
        $module = $this->opt(1);
        $ctr    = $this->opt(2);
        if (!$module) {
            $this->error("give me a module to which the model belongs!");

            return 1;
        }

        if (!($m = App::getModule($module))) {
            $this->error("the '$module' module is not found!");

            return 1;
        }

        if (!preg_match('#^[a-z][\-\w\d./_\\\\]+$#i', $ctr)) {
            $this->error("'$ctr' is an illegal hook");

            return 1;
        }
        $clzs  = preg_split('#[/\\\\]#', $ctr);
        $cls   = array_pop($clzs);
        $npath = implode(DS, $clzs);
        if ($clzs) {
            $nns = implode('\\', $clzs);
        } else {
            $nns = '';
        }
        $cls = str_replace(['-', '_', '.'], '', ucwords($cls, '-_.'));
        if ($npath) {
            $path = $m->getPath('hooks') . DS . $npath;
        } else {
            $path = $m->getPath('hooks');
        }
        if (!is_dir($path) && !@mkdir($path, 0755, true)) {
            $this->error('Cannot create handler dir: ' . $path);

            return 1;
        }
        $namespace = $m->getNamespace() . '\hooks' . ($nns ? '\\' . $nns : '\\');
        $file      = $path . DS . $cls . '.php';
        if (is_file($file)) {
            $this->error('handler: "' . $namespace . $cls . '" exists');
        }
        if (isset($options['a'])) {
            $tpl = __DIR__ . '/tpl/alter.tpl';
        } else {
            $tpl = __DIR__ . '/tpl/handler.tpl';
        }
        $bootstrap = file_get_contents($tpl);
        $bootstrap = str_replace(['{$namespace}', '{$cls}'], [$namespace, $cls], $bootstrap);
        if (!@file_put_contents($file, $bootstrap)) {
            $this->error('Cannot create handler: ' . $file);
        }

        $this->output("'$namespace$cls' handler created successfully");

        return 0;
    }
}