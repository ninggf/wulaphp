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

class ParamDataProvidor extends FieldDataProvidor {
	public function getData($search = false) {
		if ($this->option) {
			@parse_str($this->option, $data);

			return $data ? $data : [];
		}

		return [];
	}

	public function createConfigForm() {
		$form = new ParamDataProvidorForm(true);
		$form->inflateByData(['dsCfg' => $this->option]);

		return $form;
	}
}

class ParamDataProvidorForm extends FormTable {
	public $table = null;
	/**
	 * URL参数
	 * @var \backend\form\TextField
	 * @type string
	 * @layout 2,col-xs-12
	 */
	public $dsCfg;
}