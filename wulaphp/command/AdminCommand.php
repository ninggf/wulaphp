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

use wulaphp\artisan\ArtisanCommand;

class AdminCommand extends ArtisanCommand {
    public function cmd() {
        return 'admin';
    }

    public function desc() {
        return 'administrate tool for wulaphp';
    }

    protected function execute($options) {
        echo var_export($options, 1), "\n";

        return 0;
    }

    protected function subCommands() {
        $this->subCmds['create-module'] = new CreateModuleCommand('admin');
        $this->subCmds['create-ext']    = new CreateExtensionCommand('admin');
        $this->subCmds['module']        = new ModuleCommand('admin');
        $this->subCmds['router']        = new RouterCommand('admin');
        $this->subCmds['hook']          = new HookCommand('admin');

        foreach (apply_filter('artisan\init_admin_commands', []) as $cmd) {
            if ($cmd instanceof ArtisanCommand) {
                $this->subCmds[ $cmd->cmd() ] = $cmd->setParent('admin');
            }
        }
    }
}