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

use wulaphp\form\FormTable;

class JsonDataProvidor extends FieldDataProvidor {
	public function getData($search = false) {
		return (array)$this->optionAry;
	}

	public function createConfigForm() {
		$form = new JsonDataProvidorForm(true);
		$form->inflateByData(['dsCfg' => $this->option]);

		return $form;
	}
}

class JsonDataProvidorForm extends FormTable {
	public $table = null;
	/**
	 * JSON数据
	 * @var \backend\form\TextareaField
	 * @type string
	 * @layout 2,col-xs-12
	 * @option {"row":6}
	 */
	public $dsCfg;
}