<?php
namespace wulaphp\mvc\view;

/**
 * 模板视图.
 *
 * @author Guangfeng
 *
 */
class ThemeView extends View {

	/**
	 *
	 * @var \Smarty
	 */
	private $__smarty;

	/**
	 * ThemeView constructor.
	 *
	 * @param array  $data
	 * @param string $tpl
	 * @param array  $headers
	 *
	 * @filter init_smarty_engine $smarty
	 * @filter init_template_smarty_engine $smarty
	 */
	public function __construct($data = array(), $tpl = '', $headers = array('Content-Type' => 'text/html')) {
		if (!isset ($headers ['Content-Type'])) {
			$headers ['Content-Type'] = 'text/html';
		}
		parent::__construct($data, $tpl, $headers);

	}

	/**
	 * 绘制.
	 */
	public function render() {
		$tpl    = THEME_PATH . $this->tpl;
		$devMod = APP_MODE == 'dev';
		if (is_file($tpl)) {
			$this->__smarty = new \Smarty ();
			$tpl            = str_replace(DS, '/', $this->tpl);
			$tpl            = explode('/', $tpl);
			$sub            = implode(DS, array_slice($tpl, 0, -1));

			$this->__smarty->setTemplateDir(THEME_PATH);
			$this->__smarty->setCompileDir(TMP_PATH . 'themes_c' . DS . $sub);
			$this->__smarty->setCacheDir(TMP_PATH . 'themes_cache' . DS . $sub);
			$this->__smarty->setDebugTemplate(SMARTY_DIR . 'debug.tpl');
			fire('init_smarty_engine', $this->__smarty);
			fire('init_template_smarty_engine', $this->__smarty);
			if ($devMod) {
				$this->__smarty->compile_check   = true;
				$this->__smarty->caching         = false;
				$this->__smarty->debugging_ctrl  = 'URL';
				$this->__smarty->smarty_debug_id = '_debug_' . APPID;
			} else {
				$this->__smarty->compile_check = false;
			}
			$this->__smarty->error_reporting = KS_ERROR_REPORT_LEVEL;
		} else {
			throw new \Exception('The view template ' . $tpl . ' is not found');
		}
		$this->__smarty->assign($this->data);
		$this->__smarty->assign('_css_files', $this->sytles);
		$this->__smarty->assign('_js_files', $this->scripts);
		$this->__smarty->assign('_current_template_file', $this->tpl);
		@ob_start(PHP_OUTPUT_HANDLER_CLEANABLE);
		$filter  = new MustacheFilter();
		$filters = apply_filter('smarty\getFilters', ['pre' => array($filter, 'pre'), 'post' => array($filter, 'post')]);
		if ($filters) {
			foreach ($filters as $type => $cb) {
				$this->__smarty->registerFilter($type, $cb);
			}
		}
		$this->__smarty->display($this->tpl);
		$content = @ob_get_clean();

		return $content;
	}
}