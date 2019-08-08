<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\router\Router;

class RouterCommand extends ArtisanCommand {
    public function cmd() {
        return 'router';
    }

    public function desc() {
        return 'diplay registered dispatcher and routes';
    }

    public function argDesc() {
        return '[route|hook]';
    }

    protected function execute($options) {
        $op = $this->opt(1);
        if ($op == 'route') {
            $this->printRoute();
        } else {
            $this->printDis();
        }

        return 0;
    }

    private function printRoute() {
        $modules = App::modules('enabled');
        $this->output($this->cell('URL', 58), false);
        $this->output($this->cell('module', 12), false);
        $this->output($this->cell('template', 20));
        $this->output($this->cell('-', 80, '-'));
        foreach ($modules as $module) {
            $rtable = $module->getPath('route.php');
            if (is_file($rtable)) {
                $routes = include $rtable;
                $dir    = $module->getDirname();
                $ns     = $module->getNamespace();
                foreach ($routes as $route => $abc) {
                    $this->output($this->cell($dir . '/' . $route, 58), false);
                    $this->output($this->cell($ns, 12), false);
                    $this->output($abc['template']);
                }
            }
        }
    }

    private function printDis() {
        $router = Router::getRouter();
        $disps  = $router->getDispatchers();
        $this->output('Dispatchers:');
        $this->output('');
        foreach ($disps as $type => $diss) {
            if ($diss) {
                $this->output($this->cell(' type:' . $type, 10));
                foreach ($diss as $o => $dcls) {
                    $this->output('  └─ ' . $this->cell($o, 10));
                    $cnt = count($dcls) - 1;
                    foreach ($dcls as $idx => $cls) {
                        if ($idx == $cnt) {
                            $this->output('     └─ ' . $this->cell(get_class($cls), 60));
                        } else {
                            $this->output('     ├─ ' . $this->cell(get_class($cls), 60));
                        }
                    }
                }
                $this->output('');
            }
        }
    }
}