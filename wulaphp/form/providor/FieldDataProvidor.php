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
 * 表单字段数据提供器。
 *
 * @package wulaphp\form\providor
 */
class FieldDataProvidor {
    /**
     * 选项.
     * @var string|array
     */
    protected $option;
    /**
     * @var array
     */
    protected $optionAry;

    /**
     * @var \wulaphp\form\FormTable
     */
    protected $form;
    /**
     * @var \wulaphp\form\FormField
     */
    protected $field;

    private static $forms = [];

    /**
     * FieldDataProvidor constructor.
     *
     * @param \wulaphp\form\FormTable  $form
     * @param  \wulaphp\form\FormField $field
     * @param string                   $option 选项
     */
    public function __construct($form, $field, $option = '') {
        $this->option = $option;
        if (!is_array($option)) {
            $ops             = @json_decode($this->option, true);
            $this->optionAry = $ops === false ? [] : $ops;

        } else {
            $this->optionAry = $this->option;
        }
        $this->form  = $form;
        $this->field = $field;
    }

    /**
     * 配置表单.
     *
     * @return \wulaphp\form\FormTable
     */
    public function createConfigForm() {
        $clz = static::class;
        if (isset(self::$forms[ $clz ])) {
            $form = self::$forms[ $clz ];
            $form->inflateByData(['dsCfg' => $this->option]);

            return $form;
        }

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
        if ($this->option && method_exists($this->form, $this->option)) {
            return $this->form->{$this->option}(...[$search]);
        }

        return [];
    }

    /**
     * 获取一个空的数据提供器.
     * @return \wulaphp\form\providor\FieldDataProvidor
     */
    public static function emptyDatasource() {
        static $dsp = false;
        if ($dsp === false) {
            $dsp = new FieldDataProvidor(null, null, null);
        }

        return $dsp;
    }

    /**
     * 注册配置表单.
     *
     * @param string                  $clz
     * @param \wulaphp\form\FormTable $form
     */
    public final static function registerConfigForm($clz, $form) {
        assert(!empty($clz) && class_exists($clz), 'FieldDataProvidor is invalid');
        assert($form instanceof FormTable, get_class($form) . ' is not an instance of FormTable');
        self::$forms[ $clz ] = $form;
    }
}