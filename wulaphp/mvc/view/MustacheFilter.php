<?php

namespace wulaphp\mvc\view;
/**
 * Class MustacheFilter
 * @package wulaphp\mvc\view
 * @internal
 */
class MustacheFilter {
	public function pre($tpl_source, \Smarty_Internal_Template $template) {
		return str_replace(['{{', '}}', '@{','${'], ['x { y { z', 'x } y } z', '@ { @','- { -'], $tpl_source);
	}

	public function post($tpl_source, \Smarty_Internal_Template $template) {
		return str_replace(['x { y { z', 'x } y } z', '@ { @','- { -'], ['{{', '}}', '{','${'], $tpl_source);
	}
}