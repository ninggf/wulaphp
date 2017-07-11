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
	public abstract function render($opts = []);

	/**
	 * 取数据提供器.
	 *
	 * @param array $option 配置选项.
	 *
	 * @return \wulaphp\form\providor\FieldDataProvidor
	 */
	public function getDataProvidor($option = null) {
		return null;
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
}