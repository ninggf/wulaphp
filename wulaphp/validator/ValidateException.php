<?php

namespace wulaphp\validator;

use wulaphp\db\IThrowable;

/**
 * 验证异常类.
 *
 * @package wulaphp\validator
 * @author  Leo Ning <windywany@gmail.com>
 * @since   1.0.0
 */
class ValidateException extends \Exception implements IThrowable {
    private $errors = [];

    /**
     * ValidateException constructor.
     *
     * @param array $errors 字段与错误提示对应的验证错误信息.
     */
    public function __construct(array $errors) {
        parent::__construct(implode(',', $errors));
        $this->errors = $errors;
    }

    /**
     * 获取错误信息.
     * @return array 字段与错误提示对应的验证错误信息.
     */
    public function getErrors() {
        return $this->errors;
    }
}