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
abstract class Params extends TraitObject {
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
                if ($var->isStatic()) {
                    continue;#skip static properties
                }
                $name            = $var->getName();
                $this->{$name}   = rqst($name, null, $xssClean);
                $ann             = new Annotation($var);
                $fields[ $name ] = ['annotation' => $ann];
            }
        } else {
            foreach ($vars as $var) {
                if ($var->isStatic()) {
                    continue;
                }
                $name            = $var->getName();
                $ann             = new Annotation($var);
                $fields[ $name ] = ['annotation' => $ann];
            }
        }
        $this->_v__data = $fields;
        parent::__construct();
    }

    /**
     * 获取参数列表用于新增数据.
     * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
     * 如果想输出null,请使用imv('null')对参数进行赋值。
     *
     * @param array|null  $errors 错误信息，如果启用了验证功能且验证出错时的错误信息.
     * @param string|null $group  用指定组校验数据.
     *
     * @return array|null 参数数组
     */
    public function getParams(?array &$errors = null, ?string $group = null): ?array {
        try {
            $ary = $this->getData($group);
        } catch (ValidateException $e) {
            $errors = $e->getErrors();

            return null;
        }

        return $ary;
    }

    /**
     *
     * @param array|null  $errors
     * @param string|null $group 用指定组校验数据.
     *
     * @return array|null
     * @see \wulaphp\util\Params::getParams()
     */
    public function toArray(?array &$errors = null, ?string $group = null): ?array {
        return $this->getParams($errors, $group);
    }

    /**
     * 获取参数列表用于新增数据.
     * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
     * 如果想输出null,请使用imv('null')对参数进行赋值。
     *
     * @param array|null $errors 错误信息，如果启用了验证功能且验证出错时的错误信息.
     *
     * @return array|null 参数数组
     */
    public function forn(?array &$errors = null): ?array {
        return $this->getParams($errors, 'new');
    }

    /**
     * 获取参数列表用于修改，此时仅校验有值的字段.
     * 所有未明确指定值或指定值为null的参数将不会出现的结果数组里。
     * 如果想输出null,请使用imv('null')对参数进行赋值。
     *
     * @param array|null $errors 错误信息，如果启用了验证功能且验证出错时的错误信息.
     *
     * @return array|null 参数数组
     */
    public function foru(?array &$errors = null): ?array {
        return $this->getParams($errors, 'update');
    }

    /**
     * 获取数据
     *
     * @param string|null $group
     *
     * @return array|null
     * @throws \wulaphp\validator\ValidateException
     */
    public function getData(?string $group = null): array {
        $ary    = [];
        $fields = $this->_v__data;
        foreach ($fields as $field => $v) {
            $value = $this->pack($field, $this->{$field});
            if (is_null($value)) {
                continue;
            }
            $ary[ $field ] = $value;
        }

        $this->validate($ary, $group);

        return $ary;
    }

    /**
     * 打包数据.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function pack(string $field, $value) {
        return $value;
    }
}