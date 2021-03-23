<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form\provider;

use wulaphp\form\FormField;
use wulaphp\form\IForm;

/**
 * 表单字段数据提供器。
 *
 * @package wulaphp\form\providor
 */
class FieldDataProvider {
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
     * @var \wulaphp\form\IForm
     */
    protected $form;
    /**
     * @var \wulaphp\form\FormField
     */
    protected $field;

    private static $forms = [];

    /**
     * FieldDataProvider constructor.
     *
     * @param \wulaphp\form\IForm|null     $form
     * @param \wulaphp\form\FormField|null $field
     * @param string|null                  $option 选项
     */
    public function __construct(?IForm $form, ?FormField $field, ?string $option = '') {
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
     * @return \wulaphp\form\IForm
     */
    public function createConfigForm(): ?IForm {
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
    public function getData(bool $search = false) {
        if ($this->option && method_exists($this->form, $this->option)) {
            return $this->form->{$this->option}(...[$search]);
        }

        return [];
    }

    /**
     * 获取一个空的数据提供器.
     * @return \wulaphp\form\providor\FieldDataProvider
     */
    public static function emptyDatasource(): FieldDataProvider {
        static $dsp = false;
        if ($dsp === false) {
            $dsp = new FieldDataProvider(null, null, null);
        }

        return $dsp;
    }

    /**
     * 注册配置表单.
     *
     * @param string              $clz
     * @param \wulaphp\form\IForm $form
     */
    public final static function registerConfigForm(string $clz, IForm $form) {
        assert(!empty($clz) && class_exists($clz), 'FieldDataProvider is invalid');
        self::$forms[ $clz ] = $form;
    }
}