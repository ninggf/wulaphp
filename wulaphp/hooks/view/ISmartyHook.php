<?php
namespace wulaphp\hooks\view;

interface ISmartyHook {

    function init_smarty_engine($smarty);

    function init_template_smarty_engine($smarty);

    function init_view_smarty_engine($smarty);
}
