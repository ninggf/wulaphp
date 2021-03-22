<?php

namespace wulaphp\form;

use wulaphp\db\Table;
use wulaphp\util\Annotation;
use wulaphp\validator\Validator;

/**
 * 声明了字段的数据表(支持数据验证).
 *
 * @package wulaphp\form
 * @deprecated
 */
abstract class FormTable extends Table {
    use Validator;

    protected $_f__skips     = [];
    protected $_f__maps      = [];
    protected $_v__fields    = [];
    protected $_r__widgets   = null;
    protected $_r__excludes  = [];//不绘制的字段
    protected $_f__tableData = [];//数据库表数据
    protected $_v__formData  = [];//表单数据
    private   $_f__xssClean  = true;

    /**
     * FormTable constructor.
     *
     * @param bool                                                              $validte 是否解析字段.
     * @param string|array|\wulaphp\db\DatabaseConnection|\wulaphp\db\View|null $db
     *
     * @throws \wulaphp\db\DialectException
     */
    public function __construct($validte = true, $db = null) {
        parent::__construct($db);
    }

    /**
     * 根据定义的字段从请求中填充字段值.
     *
     * @param string|bool $excepts    不填充的字段,多个字段以逗号分隔.当其值为true时，$useDefault=true。
     * @param bool        $useDefault 是否使用默认值填充数据.
     * @param bool        $force      是否强制重新加载
     *
     * @return array 填充后的数组.
     */
    public final function inflate($excepts = '', $useDefault = false, $force = false): array {
        if ($this->_f__tableData && !$force) {
            return $this->_f__tableData;
        }
        $data     = [];
        $formData = [];
        if ($excepts === true) {
            $useDefault = true;
        } else {
            $excepts = $excepts ? explode(',', $excepts) : [];
        }
        foreach ($this->_v__fields as $key => $v) {
            if (in_array($key, $excepts)) {
                continue;
            }
            if (rqset($key)) {
                $value              = rqst($key, '', $v['xssClean']);
                $data[ $v['name'] ] = $this->pack($key, $value, $v);
                $formData[ $key ]   = $value;
                $this->{$key}       = $value;
            } else if ($useDefault && isset($v['default'])) {
                $formData[ $key ] = $data[ $v['name'] ] = $v['default'];
            } else if ($v['type'] == 'bool') {
                $formData[ $key ] = $data[ $v['name'] ] = 0;
                $this->{$key}     = 0;
            } else if ($v['type'] == 'array' || $v['type'] == '[]') {
                $data[ $v['name'] ] = $this->pack($key, [], $v);
                $formData[ $key ]   = [];
                $this->{$key}       = [];
            }
        }

        $this->skipFields($data);
        $this->_f__tableData = $data;
        $this->_v__formData  = $formData;

        return $data;
    }

    /**
     * 根据定义的字段从请求中填充字段值并返回表单数据.
     *
     * @param string $excepts
     * @param bool   $useDefault
     *
     * @return array
     */
    public final function formData($excepts = '', $useDefault = false) {
        return $this->inflate($excepts, $useDefault);
    }

    public final function tableData() {
        return $this->_f__tableData;
    }

    /**
     * 通过数据填充.
     *
     * @param array $data
     *
     * @return array
     */
    public final function inflateByData(array $data): array {
        $rtn      = $this->_f__tableData;
        $formData = $this->_v__formData;
        if ($data) {
            foreach ($this->_f__maps as $key => $v) {
                if (array_key_exists($key, $data)) {
                    $formData[ $v ] = $rtn[ $key ] = $this->unpack($v, $data[ $key ], $this->_v__fields[ $v ]);
                    $this->{$key}   = $rtn[ $key ];
                }
            }
        }
        $this->_f__tableData = $rtn;
        $this->_v__formData  = $formData;

        return $rtn;
    }

    /**
     * 从数据库填充数据.
     *
     * @param array $where 条件.
     *
     * @return array
     */
    public final function inflateFromDB(array $where): array {
        $data = $this->loadFromDb($where);
        if (!$data) {//加载失败时将条件做为数据填充
            $data = $where;
        }

        return $this->inflateByData($data);
    }

    /**
     * 从数据库加载数据.
     *
     * @param array|\wulaphp\db\sql\Condition $where
     *
     * @return array
     * @see  \wulaphp\form\FormTable::inflateFromDB()
     *
     */
    protected function loadFromDb($where): array {
        return $this->select($this->defaultQueryFields)->where($where)->get(0);
    }

    /**
     * 过滤数据.
     *
     * @param array $data    要过滤的数据.
     * @param bool  $formKey key是否是表单字段名.
     */
    protected function skipFields(array &$data, bool $formKey = false) {
        $keys = array_keys($data);
        foreach ($keys as $key) {
            if ($formKey) {
                $key = $this->_v__fields[ $key ]['name'];
            }
            if (isset($this->_f__skips[ $key ]) && $this->_f__skips[ $key ]) {
                unset($data[ $key ]);
            }
        }
    }

    /**
     * 将从数据库读取的字段解包.
     *
     * @param string $field
     * @param string $value
     * @param array  $options
     *
     * @return float|int|mixed
     */
    protected function unpack(string $field, string $value, array $options) {
        switch ($options['type']) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'list':
                if ($value) {
                    return @explode(',', $value);
                } else {
                    return [];
                }
            case 'array':
            case 'json':
                $value = @json_decode($value, true);

                return $value ? $value : [];
            case 'date':
                return $value ? date('Y-m-d', $value) : '';
            case 'datetime':
                return $value ? date('Y-m-d H:i:s', $value) : '';
            case 'bool':
                return boolval($value) ? 1 : 0;
            default:
                return $value;
        }
    }

    /**
     * 存到数据库之前打包字段.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return float|int|string
     */
    protected function pack(string $field, $value, array $options) {
        switch ($options['type']) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'list':
                return implode(',', $value);
            case 'array':
            case 'json':
                return @json_encode($value);
            case 'bool':
                return in_array(strtolower($value), ['on', 'yes', '1', 'enabled']) ? 1 : 0;
            case 'datetime':
            case 'date':
                $time = $value ? @strtotime($value) : 0;

                return $time === false ? 0 : $time;
            case 'number':
                if ($options['typef']) {
                    $typef = intval(trim($options['typef']));

                    return number_format($value, $typef, '.', '');
                } else {
                    return number_format($value, 3, '.', '');
                }
            default:
                return $value;
        }
    }

    /**
     * 解析字段.可解析以下注解:
     * 1. type 数据类型, int,float,number,string,bool,list,array,json,date,datetime等.
     * 2. skip 不更新到数据表字段
     * 3. name 字段名,如果不指定则与属性同名.
     * 4. 验证注解，见Validator.
     * 5. 第三方插件支持的注解.
     * 6. var  为IFormField类型
     * 7. typef 当type值为number时用来定义number_format参数.
     *
     * @throws \Exception
     */
    private function parseFields() {
        $this->_v__fields   = [];
        $refobj             = new \ReflectionObject($this);
        $dann               = new Annotation($refobj);
        $this->_f__xssClean = $dann->getBool('xssclean', true);
        unset($dann);
        $fields  = $refobj->getProperties(\ReflectionProperty::IS_PUBLIC);
        $sfields = [];
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $ann = new Annotation($field);
                if ($ann->has('type')) {
                    //表单名
                    $fname = $field->getName();
                    $fd    = $this->addField($fname, $ann, $this->{$fname});
                    if ($fd) {
                        $sfields[] = $fd;
                    }
                }
            }
        }
        if (method_exists($this, 'initialize')) {
            $this->initialize($sfields);
        }
        if ($sfields) {
            $this->defaultQueryFields = implode(',', $sfields);
        }
        fire(get_class($this) . '.onParseFields', $this);
        unset($refobj);
    }

    /**
     * 排除字段(不会绘制)
     *
     * @param string ...$fields
     */
    public function excludeFields(string ...$fields) {
        foreach ($fields as $field) {
            $this->_r__excludes[ $field ] = 1;
        }
    }

    /**
     * 添加字段.
     *
     * @param string                         $fname
     * @param \wulaphp\util\Annotation|array $ann
     * @param string                         $default
     *
     * @return string 字段名,非本表字段时请返回null。
     */
    public function addField(string $fname, $ann, $default = ''): ?string {
        if (is_array($ann)) {
            $ann = new Annotation($ann);
        }
        if ($ann->has('type')) {
            //字段名
            $fieldName = $ann->getString('name', $fname);
            //忽略值
            $this->_f__skips[ $fname ] = $ann->has('skip');
            //字段配置
            $this->_v__fields[ $fname ] = [
                'annotation' => $ann,
                'var'        => $ann->getString('var', ''),
                'name'       => $fieldName,
                'default'    => $default,
                'type'       => $ann->getString('type', 'string'),
                'typef'      => $ann->getString('typef'),
                'xssClean'   => $ann->getBool('xssclean', $this->_f__xssClean)
            ];
            if (!$this->_f__skips[ $fname ]) {
                //映射
                $this->_f__maps[ $fname ] = $fname;

                return "`{$fname}`";
            }

            return null;
        }

        return "`{$fname}`";
    }

    /**
     * 修改字段属性.
     *
     * @param string $name    字段名
     * @param array  $options 字段属性.
     */
    public function alterFieldOptions(string $name, array &$options) {
    }

    /**
     * 创建控件之前调用。
     */
    protected function beforeCreateWidgets() {
    }

    /**
     * 创建组件.
     *
     * @return array|null 组件列表.
     */
    public final function createWidgets(): ?array {
        if ($this->_r__widgets !== null) {
            return $this->_r__widgets;
        }
        $this->beforeCreateWidgets();
        $this->_r__widgets = [];
        foreach ($this->_v__fields as $key => $field) {
            if (isset($this->_r__excludes[ $key ])) {
                continue;
            }
            $cls = $field['var'];
            if ($cls && is_subclass_of($cls, FormField::class)) {
                /**@var \wulaphp\form\FormField $clz */
                $clz       = new $cls($key, $this, $field);
                $fieldName = $field['name'];
                if (isset($this->_f__tableData[ $fieldName ])) {
                    $clz->setValue($this->_f__tableData[ $fieldName ]);
                } else {
                    $clz->setValue($field['default']);
                }
                $this->_r__widgets[ $key ] = $clz;
            }
        }

        return $this->_r__widgets;
    }
}