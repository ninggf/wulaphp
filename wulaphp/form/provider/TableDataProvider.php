<?php
/**
 *
 * User: Leo Ning.
 * Date: 2017/7/14 0014 下午 5:56
 */

namespace wulaphp\form\provider;

use wulaphp\db\SimpleTable;

/**
 * 数据库数据提供器。
 *
 * ```json
 * dsCfg:{
 *
 * }
 * ```
 *
 * @package wulaphp\form\provider
 */
class TableDataProvider extends FieldDataProvider {

    public function getData(bool $search = false) {
        $options = $this->optionAry;
        if (!isset($options['table']) && !isset($options['tableName'])) {
            return [];
        }
        $table = isset($options['table']) ? $options['table'] : $options['tableName'];
        $where = isset($options['where']) ? $options['where'] : [];
        $keyId = isset($options['key']) ? $options['key'] : 'id';
        $field = isset($options['fields']) ? $options['fields'] : 'name';
        $eval  = isset($options['eval']);
        $sort  = isset($options['orderBy']) ? $options['orderBy'] : '';
        if ($sort && !is_array($sort)) {
            $sorts = explode(';', $sort);
            $sort  = [];
            foreach ($sorts as $ss) {
                $sort[] = explode(' ', $ss);
            }
        }
        $option = isset($options['option']) ? $options['option'] : [];
        $pid    = isset($options['pid']) ? $options['pid'] : false;
        if ($option && !is_array($option)) {
            $option = ['' => $option];
        }

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
        if (method_exists($this->form, 'formatData')) {
            $datas = $table->select($keyId . ',' . $field);
            if ($sort) {
                $datas->sort($sort);
            }
            $datas     = $datas->where($where)->toArray(null, $keyId);
            $fieldName = $this->field->getName();
            foreach ($datas as $k => $v) {
                $datas[ $k ] = $this->form->formatData($fieldName, $v);
            }

            return $datas;
        } else if ($pid && $option) {
            $datas = $table->select($keyId . ',' . $field);
            if ($sort) {
                $datas->sort($sort);
            }
            $opts = $option;
            $datas->where($where)->treeKey('id')->tree($opts, $keyId, $pid);

            return $opts;
        } else {
            $datas = $table->select($keyId . ',' . $field)->where($where);
            if ($sort) {
                $datas->sort($sort);
            }
            if ($option) {
                return $datas->toArray($field, $keyId, $option);
            } else {
                return $datas->toArray($field, $keyId);
            }
        }
    }
}