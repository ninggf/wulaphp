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

/**
 * 1. 一行一条数据。用=号分隔Key与Value.如：
 * "1=你好\n2=我好"
 * 将解析为 [1=>'你好',2=>'我好']
 *
 * 2. 或者用Json格式，如：
 * { "1":"你好","2":"我好"}
 *
 * @package wulaphp\form\providor
 * @since   1.0.0
 */
class LineDataProvidor extends FieldDataProvidor {
	public function getData($search = false) {
		$options = $this->option;
		$datas   = [];
		if ($options instanceof \Closure) {
			$options = $options();
		} else if (is_callable($options)) {
			$options = call_user_func($options);
		}
		if (is_array($this->optionAry)) {
			$datas = $this->optionAry;
		} else if ($options) {
			$data = explode("\n", $options);
			foreach ($data as $defaut) {
				list ($key, $d) = explode('=', $defaut);
				if ($d) {
					$datas [ $key ] = $d;
				} else {
					$datas [] = $key;
				}
			}
		}

		return $datas;
	}

	public function createConfigForm() {
		$form = new LineDataProvidorForm(true);
		$form->inflateByData(['dsCfg' => $this->option]);

		return $form;
	}
}

class LineDataProvidorForm extends FormTable {
	public $table = null;
	/**
	 * 文本数据源
	 * @var \backend\form\TextareaField
	 * @type string
	 * @layout 2,col-xs-12
	 * @option {"row":6}
	 * @note   一行一个，格式: value=文本
	 */
	public $dsCfg;
}