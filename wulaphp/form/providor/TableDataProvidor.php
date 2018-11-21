<?php
/**
 *
 * User: Leo Ning.
 * Date: 2017/7/14 0014 下午 5:56
 */

namespace wulaphp\form\providor;

use wulaphp\db\SimpleTable;
use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

/**
 * dsCfg:{
 *
 * }
 * @package wulaphp\form\providor
 */
class TableDataProvidor extends FieldDataProvidor {

    public function getData($search = false) {
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

    public function createConfigForm() {
        $form = new TableDataProvidorForm(true);
        $form->inflateByData($this->optionAry);

        return $form;
    }
}

/**
 * Class TableDataProvidorForm
 * @package wulaphp\form\providor
 * @internal
 */
class TableDataProvidorForm extends FormTable {
    use JQueryValidator;
    public $table = null;
    /**
     * 表名
     * @var \backend\form\TextField
     * @type string
     * @required
     * @layout 1,col-xs-4
     */
    public $tableName;
    /**
     * 值字段
     * @var  \backend\form\TextField
     * @type string
     * @required
     * @layout 1,col-xs-4
     */
    public $key = 'id';
    /**
     * 文本字段
     * @var \backend\form\TextField
     * @type string
     * @required
     * @layout 1,col-xs-4
     */
    public $fields = 'name';
    /**
     * 条件
     * @var \backend\form\TextField
     * @type string
     * @layout 2,col-xs-12
     */
    public $where;
    /**
     * 解析条件中的变量
     * @var \backend\form\CheckboxField
     * @type bool
     * @layout 3,col-xs-12
     */
    public $eval = 0;
    /**
     * 排序
     * @var \backend\form\TextField
     * @type string
     * @note   格式: field a,field2 d
     * @layout 4,col-xs-12
     */
    public $orderBy;
    /**
     * 默认选项提示文字
     * @var \backend\form\TextField
     * @type string
     * @layout 5,col-xs-8
     */
    public $option;
    /**
     * 树型选项父字段
     * @var \backend\form\TextField
     * @type string
     * @layout 5,col-xs-4
     */
    public $pid;
}