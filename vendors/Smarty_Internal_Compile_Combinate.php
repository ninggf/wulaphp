<?php

class Smarty_Internal_Compile_Combinate extends Smarty_Internal_CompileBase {
	/**
	 * Attribute definition: Overwrites base class.
	 *
	 * @var array
	 */
	public $shorttag_order      = array('type');
	public $required_attributes = array('type');
	/**
	 * Attribute definition: Overwrites base class.
	 *
	 * @var array
	 */
	public $optional_attributes = array('ver');

	/**
	 * Compiles code for the {combinate} tag
	 *
	 * @param array                                $args
	 *            array with attributes from parser
	 * @param Smarty_Internal_TemplateCompilerBase $compiler
	 *            compiler object
	 *
	 * @return string compiled code
	 */
	public function compile($args, Smarty_Internal_TemplateCompilerBase $compiler) {
		// check and get attributes
		$_attr = $this->getAttributes($compiler, $args);
		$file  = $_attr ['type'];
		$ver   = isset($_attr['ver']) ? $_attr['ver'] : "'1'";
		// maybe nocache because of nocache variables
		$compiler->nocache = $compiler->nocache | $compiler->tag_nocache;
		$this->openTag($compiler, 'combinate', array($file, $ver, $compiler->nocache));
		$_output = "<?php @ob_start(); ?>";

		return $_output;
	}
}

/**
 * Smarty Internal Plugin Compile Captureclose Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_CombinateClose extends Smarty_Internal_CompileBase {
	/**
	 * Compiles code for the {/combinate} tag
	 *
	 * @param array  $args
	 *            array with attributes from parser
	 * @param object $compiler
	 *            compiler object
	 *
	 * @return string compiled code
	 */
	public function compile($args, $compiler) {
		if ($compiler->nocache) {
			$compiler->tag_nocache = true;
		}

		list ($file, $ver, $compiler->nocache) = $this->closeTag($compiler, ['combinate']);

		$_output = "<?php echo(combinate_resources(@ob_get_clean(),$file,$ver));?>";

		return $_output;
	}
}