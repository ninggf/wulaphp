<?php
/**
 *
 * User: Leo Ning.
 * Date: 2017/7/14 0014 下午 5:56
 */

namespace wulaphp\form\providor;

use wulaphp\db\SimpleTable;

/**
 * dsCfg:{
 *
 * }
 * @package wulaphp\form\providor
 */
class TableDataProvidor extends FieldDataProvidor {

	public function getData($search = false) {
		$options = $this->option;
		if (!isset($options['table'])) {
			return [];
		}
		$table  = $options['table'];
		$where  = isset($options['where']) ? $options['where'] : [];
		$keyId  = isset($options['key']) ? $options['key'] : 'id';
		$field  = isset($options['fields']) ? $options['fields'] : 'name';
		$eval   = isset($options['eval']);
		$sort   = isset($options['orderBy']) ? $options['orderBy'] : '';
		$format = method_exists($this->form, 'formatData');
		if ($where && $eval) {
			$tableData = $this->form->tableData();
			$dataKeys  = array_keys($tableData);
			$dataVals  = array_values($tableData);
			array_walk($dataKeys, function (&$item) {
				$item = '{' . $item . '}';
			});
			foreach ($where as $key => $value) {
				$where[ $key ] = str_replace($dataKeys, $dataVals, $value);
			}
		}
		$table = new SimpleTable($table);
		if ($format) {
			$datas = $table->select($keyId . ',' . $field);
			if ($sort) {
				$datas->sort($sort);
			}
			$datas     = $datas->toArray(null, $keyId);
			$fieldName = $this->field->getName();
			foreach ($datas as $k => $v) {
				$datas[ $k ] = $this->form->formatData($fieldName, $v);
			}

			return $datas;
		} else {
			$datas = $table->select($keyId . ',' . $field);
			if ($sort) {
				$datas->sort($sort);
			}

			return $datas->toArray($field, $keyId);
		}
	}
}