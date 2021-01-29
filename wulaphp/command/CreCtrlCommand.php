<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;

/**
 * Class CreCtrlCommand
 * @package wulaphp\command
 * @internal
 */
class CreCtrlCommand extends ArtisanCommand {
    public function cmd() {
        return 'controller';
    }

    public function desc() {
        return 'Create a Controller';
    }

    public function argDesc() {
        return '<module> <name>';
    }

    protected function getOpts() {
        return ['t::' => 'create tpl with specified view engine'];
    }

    protected function execute($options) {
        $module = $this->opt(1);
        $ctr    = $this->opt(2);
        if (!$module) {
            $this->error("give me a module to which the controller belongs!");

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

        if (!$ctr) {
            $ctr = 'index';
        }

        if (!preg_match('/^[a-z][\w\d_]+$/i', $ctr)) {
            $this->error("'$ctr' is an illegal controller name");

            return 1;
        }
        $clz = ucfirst($ctr);
        if ($subModule) {
            $viewp     = $m->getPath($subModule . DS . 'views' . DS . strtolower($ctr)) . DS;
            $path      = $m->getPath($subModule . DS . 'controllers');
            $namespace = $m->getNamespace() . '\\' . $subModule;
        } else {
            $viewp     = $m->getPath('views' . DS . strtolower($ctr));
            $path      = $m->getPath('controllers');
            $namespace = $m->getNamespace();
        }
        $file = $path . DS . $clz . '.php';

        if (is_file($file)) {
            $this->error('controller: "' . $clz . '" exists');

            return 1;
        }

        if (!is_dir($path) && !@mkdir($path, 0755, true)) {
            $this->error('Cannot create controller dir: ' . $path);

            return 1;
        }

        $view    = aryget('t', $options, 'smarty');
        $vfile   = null;
        $viewCnt = '';

        switch ($view) {
            case 'php':
            case 'html':
                $vfunc   = 'return pview($data)';
                $vfile   = $viewp . DS . 'index.php';
                $viewCnt = "<?php\necho \$module, ' is ready';\n";
                break;
            case 'excel':
                $vfunc = 'return excel($data)';
                break;
            case 'xml':
                $vfunc = 'return xmlview($data)';
                break;
            case 'json':
                $vfunc = 'return $data';
                break;
            case 'smarty':
            default:
                $vfunc   = 'return view($data)';
                $vfile   = $viewp . DS . 'index.tpl';
                $viewCnt = "{\$module} is ready\n";
        }
        $bootstrap = file_get_contents(__DIR__ . '/tpl/controller.tpl');
        $bootstrap = str_replace(['{$namespace}', '{$module}', '{$vfunc}'], [$namespace, $clz, $vfunc], $bootstrap);
        if (!@file_put_contents($file, $bootstrap)) {
            $this->error('Cannot create controller: ' . $file);
        }
        if ($vfile && !is_file($vfile)) {
            if (!is_dir($viewp) && !@mkdir($viewp, 0755, true)) {
                $this->error('Cannot create view dir: ' . $viewp);
                @unlink($file);

                return 1;
            }
            if (!file_put_contents($vfile, $viewCnt)) {
                $this->error('Cannot create view: ' . $file);
                @unlink($file);

                return 1;
            }
        }
        $this->output("'$clz' controller created successfully");

        return 0;
    }
}