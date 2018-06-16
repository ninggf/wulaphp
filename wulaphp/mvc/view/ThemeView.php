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
	private $__mustache = false;

	/**
	 * ThemeView constructor.
	 *
	 * @param array  $data
	 * @param string $tpl
	 * @param array  $headers
	 *
	 * @filter init_smarty_engine $smarty
	 * @filter init_template_smarty_engine $smarty
	 * @throws \Exception
	 */
	public function __construct($data = [], $tpl = '', $headers = ['Content-Type' => 'text/html']) {
		if (!isset ($headers ['Content-Type'])) {
			$headers ['Content-Type'] = 'text/html';
		}
		parent::__construct($data, $tpl, $headers);
	}

	/**
	 * 绘制.
	 * @throws \Exception
	 */
	public function render() {
		$tpl    = THEME_PATH . $this->tpl;
		$devMod = APP_MODE != 'pro';
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
			$this->__smarty->compile_check = 1;
			if ($devMod) {
				$this->__smarty->caching         = false;
				$this->__smarty->debugging_ctrl  = 'URL';
				$this->__smarty->smarty_debug_id = '_debug_wula';
			}
			$this->__smarty->error_reporting = KS_ERROR_REPORT_LEVEL;
		} else {
			throw new \Exception(__('The template %s is not found', $tpl));
		}
		$this->__smarty->assign($this->data);
		$this->__smarty->assign('_css_files', $this->sytles);
		$this->__smarty->assign('_js_files', $this->scripts);
		$this->__smarty->assign('_current_template_file', $this->tpl);
		@ob_start();
		if ($this->__mustache) {
			$filter  = new MustacheFilter();
			$filters = apply_filter('smarty\getFilters', ['pre' => [[$filter, 'pre']], 'post' => [[$filter, 'post']]]);
		} else {
			$filters = apply_filter('smarty\getFilters', ['pre' => [], 'post' => []]);
		}

		if ($filters) {
			foreach ($filters as $type => $cbs) {
				foreach ($cbs as $cb) {
					$this->__smarty->registerFilter($type, $cb);
				}
			}
		}
		$this->__smarty->display($this->tpl);
		$content = @ob_get_clean();

		return $content;
	}

	/**
	 * 启用mustache.
	 *
	 * @return $this
	 */
	public function mustache() {
		$this->__mustache = true;

		return $this;
	}
}