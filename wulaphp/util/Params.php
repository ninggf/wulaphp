<?php

namespace wulaphp\util;

use wulaphp\validator\ValidateException;
use wulaphp\validator\Validator;

/**
 * 参数定义类，用于快速给方法提供准确参数.
 * 调用方法:
 * <code>
 *  $param = $params->getParams($error);
 * </code>
 *
 * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
 * 如果想输出null,请使用imv('null')对参数进行赋值。
 * 只需定义public属性即可。
 */
abstract class Params {
    use Validator;

    private $_v__data = [];

    /**
     * Params constructor.
     *
     * @param bool $inflate  是否填充
     * @param bool $xssClean 清洗
     */
    public function __construct($inflate = false, $xssClean = true) {
        $fields = [];
        $obj    = new \ReflectionObject($this);
        $vars   = $obj->getProperties(\ReflectionProperty::IS_PUBLIC);
        if ($inflate) {
            foreach ($vars as $var) {
                $name            = $var->getName();
                $this->{$name}   = rqst($name, null, $xssClean);
                $ann             = new Annotation($var);
                $fields[ $name ] = ['annotation' => $ann];
            }
        } else {
            foreach ($vars as $var) {
                $name            = $var->getName();
                $ann             = new Annotation($var);
                $fields[ $name ] = ['annotation' => $ann];
            }
        }
        if ($fields) {
            $this->onInitValidator($fields);
        }
        $this->_v__data = $fields;
    }

    /**
     * 获取参数列表.
     * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
     * 如果想输出null,请使用imv('null')对参数进行赋值。
     *
     * @param array|null $errors 错误信息，如果启用了验证功能且验证出错时的错误信息.
     *
     * @return array|null 参数数组
     */
    public function getParams(&$errors = null): ?array {
        $ary    = [];
        $fields = $this->_v__data;
        foreach ($fields as $field => $v) {
            $value = $this->{$field};
            if (is_null($value)) {
                continue;
            }
            $ary[ $field ] = $value;
        }
        unset($obj, $vars, $var);
        if ($fields) {
            try {
                $this->validateNewData($ary);
            } catch (ValidateException $e) {
                $errors = $e->getErrors();

                return null;
            }
        }

        return $ary;
    }
}