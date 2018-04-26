<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Smarty Internal Plugin Compile Ctsp Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Ctsp extends Smarty_Internal_CompileBase {
	/**
	 * Attribute definition: Overwrites base class.
	 *
	 * @var array
	 * @see Smarty_Internal_CompileBase
	 */
	public $required_attributes = ['var'];
	/**
	 * Attribute definition: Overwrites base class.
	 *
	 * @var array
	 * @see Smarty_Internal_CompileBase
	 */
	public $optional_attributes = ['_any'];

	/**
	 * Compiles code for the {ctsp} tag
	 *
	 * @param array  $args      array with attributes from parser
	 * @param object $compiler  compiler object
	 * @param array  $parameter array with compilation parameter
	 *
	 * @return string compiled code
	 */
	public function compile($args, $compiler, $parameter) {
		$_attr = $this->getAttributes($compiler, $args);
		$name  = isset($_attr ['for']) ? $_attr ['for'] : '__this';

		$pitem  = "'" . trim($_attr ['var'], '\'"') . "'";
		$render = "'default'";
		if (isset ($_attr ['render']) && !empty ($_attr ['render'])) {
			$render = $_attr ['render'];
		}
		$loop = true;
		if (isset ($_attr ['loop']) && empty ($_attr ['loop'])) {
			$loop = false;
		}
		if ($name == '__this') {
			$pname = "";
		} else {
			$pname = trim($name, "'\"");
			$pname = "_cts_{$pname}_data";
		}

		$compiler->nocache = $compiler->nocache | $compiler->tag_nocache;
		unset ($_attr ['for'], $_attr ['render'], $_attr['item'], $_attr['nocache'], $_attr['var']);
		$argstr = smarty_argstr($_attr);
		$this->openTag($compiler, 'ctsp', ['ctsp', $compiler->nocache, $pitem, $loop]);
		$output = "<?php ";
		if (!$pname) {
			$pname  = '_cts_cur_page_data';
			$output .= '$_cts_cur_page_data = new wulaphp\mvc\model\CtsData();';
		}
		$output .= "if(isset(\${$pname})){";
		$output .= "\$_smarty_tpl->tpl_vars[$pitem] = new Smarty_Variable();\$_ctsp_from_pages = \${$pname}->getPageList($render, $argstr);\n";
		if ($loop) {
			$output .= "if (is_string(\$_ctsp_from_pages)){ echo \$_ctsp_from_pages; } else {\n";
			$output .= "foreach (\$_ctsp_from_pages as \$_smarty_tpl->tpl_vars[$pitem]->key => \$_smarty_tpl->tpl_vars[$pitem]->value){?>\n";
		} else {
			$output .= "\$_smarty_tpl->tpl_vars[$pitem]->value = \$_ctsp_from_pages;?>\n";
		}

		return $output;
	}
}

/**
 * Smarty Internal Plugin Compile ctspclose Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Ctspclose extends Smarty_Internal_CompileBase {
	/**
	 * Compiles code for the {/ctsp} tag
	 *
	 * @param array  $args      array with attributes from parser
	 * @param object $compiler  compiler object
	 * @param array  $parameter array with compilation parameter
	 *
	 * @return string compiled code
	 */
	public function compile($args, $compiler, $parameter) {
		// must endblock be nocache?
		if ($compiler->nocache) {
			$compiler->tag_nocache = true;
		}
		list (, $compiler->nocache, , $loop) = $this->closeTag($compiler, ['ctsp']);
		if ($loop) {
			return "<?php }}} ?>";
		} else {
			return "<?php } ?>";
		}
	}
}