<?php

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

/**
 * Class CreateComamnd
 * @package wulaphp\command
 * @internal
 */
class CreateComamnd extends ArtisanCommand {
    public function cmd() {
        return 'create';
    }

    public function desc() {
        return 'create module,extension,controller,model and handler';
    }

    protected function execute($options) {
        return 0;
    }

    protected function subCommands() {
        $this->subCmds['controller'] = new CreCtrlCommand('create');
        $this->subCmds['extension']  = new CreateExtensionCommand('create');
        $this->subCmds['hook']       = new CreHookCommand('create');
        $this->subCmds['model']      = new  CreModelCommand('create');
        $this->subCmds['module']     = new CreateModuleCommand('create');
    }
}