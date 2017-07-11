<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form\providor;

class FieldDataProvidor {
	/**
	 * 选项.
	 * @var array
	 */
	protected $option;
	/**
	 * @var \wulaphp\form\FormTable
	 */
	protected $form;
	/**
	 * @var \wulaphp\form\FormField
	 */
	protected $field;

	/**
	 * FieldDataProvidor constructor.
	 *
	 * @param \wulaphp\form\FormTable  $form
	 * @param  \wulaphp\form\FormField $field
	 * @param array                    $option 选项
	 */
	public function __construct($form, $field, $option = []) {
		$this->option = $option;
		$this->form   = $form;
		$this->field  = $field;
	}

	/**
	 * 配置表单.
	 *
	 * @return \wulaphp\form\FormTable
	 */
	public function createConfigForm() {
		return null;
	}

	/**
	 * 获取数据.
	 *
	 * @param bool $search
	 *
	 * @return mixed
	 */
	public function getData($search = false) {
		return '';
	}
}