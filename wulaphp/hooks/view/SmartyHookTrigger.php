<?php
namespace wulaphp\hooks\view;

use wulaphp\plugin\Trigger;

class SmartyHookTrigger extends Trigger implements ISmartyHook {

    public function init_smarty_engine($smarty) {
        $this->delegateFire ( 'init_smarty_engine', array (
            $smarty
        ) );
    }

    public function init_view_smarty_engine($smarty) {
        $this->delegateFire ( 'init_view_smarty_engine', array (
            $smarty
        ) );
    }

    public function init_template_smarty_engine($smarty) {
        $this->delegateFire ( 'init_template_smarty_engine', array (
            $smarty
        ) );
    }
}