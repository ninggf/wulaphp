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
use wulaphp\app\Module;
use wulaphp\artisan\ArtisanCommand;

class ModuleCommand extends ArtisanCommand {
    public function cmd() {
        return 'module';
    }

    public function desc() {
        return 'manage module of wulaphp';
    }

    public function argDesc() {
        return '<list|install|uninstall|upgrade|stop|start> <module>';
    }

    protected function execute($options) {
        $op = $this->opt(1, 'list');
        if (!in_array($op, ['list', 'install', 'uninstall', 'upgrade', 'stop', 'start'])) {
            $this->help('unknow command: ' . $op);
            exit(1);
        }

        switch ($op) {
            case 'list':
                $modules = App::modules('all');
                $idLen   = 10;
                $dirLen  = 10;
                $verLen  = 10;
                foreach ($modules as $id => $module) {
                    $l1 = mb_strlen($id);
                    if ($l1 > $idLen) {
                        $idLen = $l1;
                    }
                    $l3 = mb_strlen($module->getDirname());
                    if ($l3 > $dirLen) {
                        $dirLen = $l3;
                    }
                    if ($module->upgradable) {
                        $verLen += 14;
                    }
                }
                echo "\n", str_pad('-', '80', '-'), "\n";
                echo $this->cell('ID', $idLen), ' | ', $this->cell('Dir', $dirLen), ' | ';
                echo $this->cell('Version', $verLen), " | ", $this->cell('Status', 10), " | Name\n";
                echo str_pad('-', '80', '-'), "\n";
                foreach ($modules as $id => $module) {
                    echo $this->cell($id, $idLen), ' | ', $this->cell(App::id2dir($id), $dirLen), ' | ';
                    if ($module->upgradable) {
                        echo $this->cell($module->installedVersion . ' -> ' . $module->getCurrentVersion(), $verLen);
                    } else {
                        echo $this->cell($module->installedVersion, $verLen);
                    }
                    echo ' | ', $this->colorText($this->cell($this->statusText($module), 10), $module), ' | ', $module->getName(), "\n";
                }
                echo "\n";
                break;
            default:
                $module = $this->opt(2);
                if (empty($module)) {
                    $this->help('Give me a module please ');
                    exit(1);
                }
                $moduleIns = App::getModule($module);
                if (empty($moduleIns)) {
                    $this->error('module "' . $module . '" not found');
                    exit(1);
                }
                $db = null;
                try {
                    $db = App::db();
                } catch (\Exception $e) {

                }
                exit($this->{$op}(...[$moduleIns, $db]));
        }
    }

    protected function install(Module $module, $db) {
        $rst = $module->install($db);
        if (!$rst) {
            $this->error('Cannot install "' . $module->getNamespace() . '"');

            return 1;
        }
        echo 'install ' . $this->color->str('successfully', 'green'), "\n";

        return 0;
    }

    protected function uninstall(Module $module) {
        $rst = $module->uninstall();
        if (!$rst) {
            $this->error('Cannot uninstall "' . $module->getNamespace() . '"');

            return 1;
        }
        echo 'uninstall ' . $this->color->str('successfully', 'green'), "\n";

        return 0;
    }

    protected function upgrade(Module $module, $db) {
        $rst = $module->upgrade($db, $module->getCurrentVersion(), $module->installedVersion);
        if (!$rst) {
            $this->error('Cannot upgrade "' . $module->getNamespace() . '"');

            return 1;
        }
        echo 'upgrade ' . $this->color->str('successfully', 'green'), "\n";

        return 0;
    }

    protected function stop(Module $module) {
        $rst = $module->stop();
        if (!$rst) {
            $this->error('Cannot stop "' . $module->getNamespace() . '"');

            return 1;
        }
        echo 'stop ' . $this->color->str('successfully', 'green'), "\n";

        return 0;
    }

    protected function start(Module $module) {
        $rst = $module->stop();
        if (!$rst) {
            $this->error('Cannot start "' . $module->getNamespace() . '"');

            return 1;
        }
        echo 'start ' . $this->color->str('successfully', 'green'), "\n";

        return 0;
    }

    private function statusText(Module $module) {
        if ($module->installed) {
            if ($module->enabled) {
                if ($module->upgradable) {
                    return 'Upgradable';
                }

                return 'Running';
            } else {
                return 'Stopped';
            }
        }

        return 'Uninstall';
    }

    private function colorText($text, Module $module) {
        if ($module->installed) {
            if ($module->enabled) {
                if ($module->upgradable) {
                    return $this->color->str($text, 'yellow');
                }

                return $this->color->str($text, 'green');
            } else {
                return $this->color->str($text, 'red');
            }
        }

        return $this->color->str($text, 'light_gray');
    }
}