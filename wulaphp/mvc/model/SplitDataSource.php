<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\model;

use wulaphp\form\FormTable;

/**
 * 分隔content参数指定的内容.
 *
 * @package wulaphp\mvc\model
 */
class SplitDataSource extends CtsDataSource {
	public function getName() {
		return '分隔数据';
	}

	/**
	 * @param array                          $con
	 * @param \wulaphp\db\DatabaseConnection $db
	 * @param \wulaphp\router\UrlParsedInfo  $pageInfo
	 * @param array                          $tplvar
	 *
	 * @return \wulaphp\mvc\model\CtsData
	 */
	protected function getData($con, $db, $pageInfo, $tplvar) {
		$content = isset($con['content']) ? $con['content'] : '';
		if (!$content) {
			return new CtsData([], 0);
		}
		$sp = aryget('sp', $con, ',');
		if (isset($con['r']) && $con['r']) {
			$content = @preg_split('#' . $con['r'] . '#', $content);
		} else {
			$content = @explode($sp, $content);
		}
		$contents = [];
		foreach ($content as $c) {
			$contents[] = ['val' => $c];
		}

		return new CtsData($contents, count($contents));
	}

	public function getCols() {
		return ['val' => '值'];
	}

	public function getCondForm() {
		return new SplitDataSourceForm(true);
	}
}

class SplitDataSourceForm extends FormTable {
	public $table = null;
	/**
	 * 要分隔的内容
	 * @var \backend\form\TextField
	 * @type string
	 * @layout 1,col-xs-12
	 */
	public $content;
	/**
	 * 分隔符(正则)
	 * @var \backend\form\TextField
	 * @type string
	 * @layout 2,col-xs-12
	 */
	public $sp = ',';
	/**
	 * 使用正则
	 * @var \backend\form\CheckboxField
	 * @type int
	 * @layout 3,col-xs-12
	 */
	public $r = 0;
}