<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form;

use wulaphp\form\providor\FieldDataProvidor;

/**
 * Class FormField
 * @package wulaphp\form
 */
abstract class FormField implements \ArrayAccess {
	/**
	 * @var \wulaphp\form\FormTable
	 */
	protected $form = null;
	/**
	 * @var \wulaphp\util\Annotation
	 */
	protected $options = [];
	protected $value   = '';
	protected $name    = '';

	/**
	 * FormField constructor.
	 *
	 * @param string                  $name 表单字段名.
	 * @param \wulaphp\form\FormTable $form
	 * @param array                   $options
	 */
	public function __construct($name, $form, $options = []) {
		$this->name    = $name;
		$this->form    = $form;
		$this->options = $options;
		/**@var \wulaphp\util\Annotation */
		$ann = $options['annotation'];
		if ($ann) {
			$opts ['label']      = $ann->getString('label', $ann->getDoc());
			$opts ['render']     = $ann->getString('render');
			$opts ['wrapper']    = $ann->getString('wrapper');
			$opts ['layout']     = $ann->getString('layout');
			$opts ['dataSource'] = $ann->getString('dataSource', $ann->getString('see', null));
			$opts ['dsCfg']      = $ann->getString('dsCfg');
			$opts ['note']       = $ann->getString('note');
			$opts1               = $ann->getJsonArray('option', []);
			$this->options       = array_merge($this->options, $opts, $opts1);
			$form->alterFieldOptions($name, $this->options);
		}
	}

	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * 配置字段.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function opt($name, $value) {
		$this->options[ $name ] = $value;

		return $this;
	}

	/**
	 * 通过数组配置字段.
	 *
	 * @param array $options
	 *
	 * @return $this
	 */
	public function optionsByArray($options) {
		if ($options) {
			$this->options = array_merge($this->options, $options);
		}

		return $this;
	}

	public function getValue() {
		return $this->value;
	}

	public function layout() {
		if (isset($this->options['layout'])) {
			return explode(',', $this->options['layout']);
		} else {
			return [];
		}
	}

	/**
	 * 组件名称.
	 *
	 * @return string
	 */
	public abstract function getName();

	/**
	 * 绘制.
	 *
	 * @param array $opts
	 *
	 * @return string
	 */
	public function render($opts = []) {
		if ($this->options['render'] && is_callable([$this->form, $this->options['render']])) {
			return call_user_func_array([$this->form, $this->options['render']], [$this, $opts]);
		} else {
			$html = $this->renderWidget($opts);
			if ($this->options['wrapper'] && is_callable([$this->form, $this->options['wrapper']])) {
				$html = call_user_func_array([$this->form, $this->options['wrapper']], [$html, $this, $opts]);
			}

			return $html;
		}
	}

	public function getOptions() {
		return $this->options;
	}

	/**
	 * 取数据提供器.
	 *
	 * @return \wulaphp\form\providor\FieldDataProvidor
	 */
	public function getDataProvidor() {
		$option = $this->options;
		if (!isset($option['dataSource'])) {
			return FieldDataProvidor::emptyDatasource();
		}

		$dsp = $option['dataSource'];
		if (!is_subclass_of($dsp, FieldDataProvidor::class)) {
			return FieldDataProvidor::emptyDatasource();
		}

		return new $dsp($this->form, $this, isset($this->options['dsCfg']) ? $this->options['dsCfg'] : '');
	}

	public function offsetExists($offset) {
		return isset($this->options[ $offset ]);
	}

	public function offsetGet($offset) {
		return $this->options[ $offset ];
	}

	public function offsetSet($offset, $value) {
		$this->options[ $offset ] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->options[ $offset ]);
	}

	/**
	 * 绘制.
	 *
	 * @param array $opts
	 *
	 * @return string
	 */
	protected abstract function renderWidget($opts);

	/**
	 * 字段配置.
	 *
	 * @return array
	 */
	protected function config() {
		return [];
	}
}