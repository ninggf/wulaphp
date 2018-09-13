<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class CreateExtensionCommand extends ArtisanCommand {
    public function cmd() {
        return 'create-ext';
    }

    public function desc() {
        return 'Create extension structure for your';
    }

    protected function execute($options) {
        $extension = $this->opt(1);//第三个参数
        if (!$extension) {
            $this->help('missing <name>');

            return 1;
        }
        if (!preg_match('#^[a-z][a-z_\d]+(/[a-z][a-z_\-\d]+)*$#', $extension)) {
            $this->error('illegal namespace: ' . $this->color->str($extension, 'white', 'red'));

            return 1;
        }
        $composerName = $extension;
        $extensions   = explode('/', $extension);
        $extension    = implode('\\', $extensions);
        $path         = implode(DS, $extensions);

        if (is_dir(EXTENSIONS_PATH . $path)) {
            $this->error('the directory ' . $this->color->str($path, 'white', 'red') . ' is exist');

            return 1;
        }

        if (!mkdir(EXTENSIONS_PATH . $path, 0755, true)) {
            $this->error('cannot create the directory ' . $this->color->str($path, 'white', 'red'));

            return 1;
        }
        if (isset($options['b'])) {
            $fileName = array_pop($extensions);
            // 创建引导文件
            $bootstrap = file_get_contents(__DIR__ . '/tpl/extension.tpl');
            $bootstrap = str_replace(['{$namespace}', '{$extension}'], [$extension, ucfirst($fileName)], $bootstrap);
            file_put_contents(EXTENSIONS_PATH . $path . DS . $fileName . '.php', $bootstrap);
        }
        if (isset($options['c'])) {
            $bootstrap = file_get_contents(__DIR__ . '/tpl/composer.json');
            $bootstrap = str_replace(['{$name}', '{$type}'], [$composerName, 'extension'], $bootstrap);
            file_put_contents(EXTENSIONS_PATH . $path . DS . 'composer.json', $bootstrap);
        }
        $this->success('the extension ' . $this->color->str($composerName, 'green') . ' is created successfully');

        return 0;
    }

    public function getOpts() {
        return ['b' => 'create the bootstrap file for the extension', 'c' => 'create composer.json for extension'];
    }

    protected function argDesc() {
        return '<name>';
    }
}