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
	 * @param string|array $tpl
	 * @param array        $data
	 *
	 * @return \wulaphp\mvc\view\View
	 */
	protected function render($tpl = null, $data = []) {
		if ($this instanceof Controller) {
			if (is_array($tpl)) {
				$data = $tpl;
				$tpl  = null;
			}
			$layout = '~' . $this->layout;
			$data   = $this->onInitLayoutData($data);
			if ($tpl && $tpl{0} == '~') {
				$tpl = substr($tpl, 1);
				$tpl = $this->realPath($tpl . '.tpl');
			} else if ($tpl) {
				$path = str_replace(['\\', 'controllers'], [DS, 'views'], $this->reflectionObj->getNamespaceName());
				$tpl  = $this->realPath($path . DS . $tpl . '.tpl');
			} else {
				$path = str_replace(['\\', 'controllers'], [DS, 'views'], $this->reflectionObj->getNamespaceName());
				$tpl  = $this->realPath($path . DS . $this->ctrName . DS . $this->action . '.tpl');
			}
			$data['workspaceView'] = $tpl;
			$view                  = view($layout, $data);

			return $view;
		}

		return null;
	}

	protected final function onInitLayoutSupport() {
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
	protected function onInitLayoutData($data) {
		return $data;
	}

	private function realPath($path) {
		$tpls    = explode('/', $path);
		$tpls[0] = App::id2dir($tpls[0]);
		$tpl     = implode('/', $tpls);

		return $tpl;
	}
}