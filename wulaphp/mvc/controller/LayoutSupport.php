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
		throw new \Exception(get_class($this) . ' is not instance of wulaphp\mvc\controller');
	}

	protected function onInitLayoutSupport() {
		if (!isset($this->layout)) {
			throw new \BadMethodCallException('the layout property of ' . get_class($this) . ' is not found!');
		}
	}

	/**
	 * 初始化Layout数据.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function onInitLayoutData($data) {
		return $data;
	}
}