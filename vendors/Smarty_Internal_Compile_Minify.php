<?php

class Smarty_Internal_Compile_Minify extends Smarty_Internal_CompileBase {
	public $shorttag_order      = ['type'];
	public $optional_attributes = ['type'];

	/**
	 * Compiles code for the {minify} tag
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

		$buffer = isset ($_attr ['type']) ? $_attr ['type'] : "'js'";

		// maybe nocache because of nocache variables
		$compiler->nocache = $compiler->nocache | $compiler->tag_nocache;
		$this->openTag($compiler, 'minify', [$buffer, $compiler->nocache]);
		$_output = "<?php @ob_start(); ?>";

		return $_output;
	}
}

class Smarty_Internal_Compile_MinifyClose extends Smarty_Internal_CompileBase {
	/**
	 * Compiles code for the {/minify} tag
	 *
	 * @param array  $args
	 *            array with attributes from parser
	 * @param object $compiler
	 *            compiler object
	 *
	 * @return string compiled code
	 */
	public function compile($args, $compiler) {
		// check and get attributes

		// must endblock be nocache?
		if ($compiler->nocache) {
			$compiler->tag_nocache = true;
		}

		list ($buffer, $compiler->nocache) = $this->closeTag($compiler, ['minify']);

		$_output = "<?php echo(minify_resources(@ob_get_clean(),$buffer));?>";

		return $_output;
	}
}
