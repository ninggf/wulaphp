<?php

namespace wulaphp\mvc\controller;

use wulaphp\app\App;

/**
 * Class LayoutSupport
 * @property string $layout 布局模板.
 * @package wulaphp\mvc\controller
 */
trait LayoutSupport {
	/**
	 * @param string $tpl
	 * @param array  $data
	 *
	 * @return \wulaphp\mvc\view\View
	 * @throws \Exception
	 */
	protected function doLayout($tpl, $data = []) {
		if ($this instanceof Controller) {
			$layout = '~' . $this->layout;
			$data   = $this->onInitLayoutData($data);
			if ($tpl{0} == '~') {
				$tpl     = substr($tpl, 1);
				$tpls    = explode('/', $tpl);
				$tpls[0] = App::id2dir($tpls[0]);
				$tpl     = implode('/', $tpls) . '.tpl';
				unset($tpls[0]);
			} else {
				$path = $this->module->getDirname();
				$tpl  = $path . '/views/' . $tpl . '.tpl';
			}
			$data['workspaceView'] = $tpl;
			$view                  = view($layout, $data);

			return $view;
		}
		$msg = __('%s is not instance of wulaphp\mvc\Controller', get_class($this));
		throw new \Exception($msg);
	}

	protected function onInitLayoutSupport() {
		if (!isset($this->layout)) {
			$msg = __('The layout property of %s is not found', get_class($this));
			throw new \BadMethodCallException($msg);
		}
	}

	/**
	 * 初始化Layout数据.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected abstract function onInitLayoutData($data);
}