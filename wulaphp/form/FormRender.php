<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form;

use wulaphp\mvc\view\Renderable;

/**
 * 表单绘制器。
 *
 * @package wulaphp\form
 */
abstract class FormRender implements Renderable {
    /**
     * 要绘制的表单.
     * @var \wulaphp\form\FormTable
     */
    protected $form;
    /**
     * 选项.
     * @var array
     */
    protected $options;

    /**
     * FormRender constructor.
     *
     * @param \wulaphp\form\FormTable $form
     * @param array                   $options
     */
    public function __construct(FormTable $form, array $options = []) {
        $this->form    = $form;
        $this->options = $options;
    }

    /**
     * 设置选项.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return \wulaphp\form\FormRender
     */
    public function opt(string $name, $value): FormRender {
        $this->options[ $name ] = $value;

        return $this;
    }
}