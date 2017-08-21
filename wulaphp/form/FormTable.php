<?php

namespace wulaphp\form;

use wulaphp\db\Table;
use wulaphp\util\Annotation;
use wulaphp\validator\Validator;

/**
 * 声明了字段的数据表.
 *
 * @package wulaphp\form
 */
abstract class FormTable extends Table {
	use Validator;
	protected $_skips_    = [];
	protected $_maps_     = [];
	protected $_fields    = [];
	protected $_widgets   = null;
	protected $_excludes  = [];//不绘制的字段
	protected $_tableData = [];//数据库表数据
	protected $_formData  = [];//表单数据
	private   $_xssClean  = true;

	/**
	 * FormTable constructor.
	 *
	 * @param string|array|\wulaphp\db\DatabaseConnection|\wulaphp\db\View $db
	 */
	public function __construct($db = null) {
		$this->parseFields();
		parent::__construct($db);
	}

	/**
	 * 根据定义的字段从请求中填充字段值.
	 *
	 * @param string|bool $excepts    不填充的字段,多个字段以逗号分隔.当其值为true时，$useDefault=true。
	 * @param bool        $useDefault 是否使用默认值填充数据.
	 *
	 * @return array 填充后的数组.
	 */
	public final function inflate($excepts = '', $useDefault = false) {
		if ($this->_tableData) {
			return $this->_tableData;
		}
		$data     = [];
		$formData = [];
		if ($excepts === true) {
			$useDefault = true;
		} else {
			$excepts = $excepts ? explode(',', $excepts) : [];
		}
		foreach ($this->_fields as $key => $v) {
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
			}
		}

		$this->filterFields($data);
		$this->_tableData = $data;
		$this->_formData  = $formData;

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
		$this->inflate($excepts, $useDefault);

		return $this->_formData;
	}

	public final function tableData() {
		return $this->_tableData;
	}

	/**
	 * 通过数据填充.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public final function inflateByData($data) {
		$rtn      = [];
		$formData = [];
		if ($data) {
			foreach ($this->_maps_ as $key => $v) {
				if (isset($data[ $key ])) {
					$formData[ $v ] = $rtn[ $key ] = $this->unpack($v, $data[ $key ], $this->_fields[ $v ]);
					$this->{$key}   = $rtn[ $key ];
				}
			}
		}
		$this->_tableData = $rtn;
		$this->_formData  = $formData;

		return $rtn;
	}

	/**
	 * 从数据库填充数据.
	 *
	 * @param array $where 条件.
	 *
	 * @return array
	 */
	public final function inflateFromDB($where) {
		$data = $this->loadFromDb($where);
		if (!$data) {//加载失败时将条件做为数据填充
			$data = $where;
		}

		return $this->inflateByData($data);
	}

	/**
	 * 从数据库加载数据.
	 * @see  \wulaphp\form\FormTable::inflateFromDB()
	 *
	 * @param array $where
	 *
	 * @return array
	 */
	protected function loadFromDb($where) {
		$data = $this->select($this->defaultQueryFields)->where($where)->get(0);

		return $data;
	}

	/**
	 * 过滤数据.
	 *
	 * @param array $data    要过滤的数据.
	 * @param bool  $formKey key是否是表单字段名.
	 */
	protected function filterFields(&$data, $formKey = false) {
		$keys = array_keys($data);
		foreach ($keys as $key) {
			if ($formKey) {
				$key = $this->_fields[ $key ]['name'];
			}
			if (isset($this->_skips_[ $key ]) && $this->_skips_[ $key ]) {
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
	protected function unpack($field, $value, $options) {
		switch ($options['type']) {
			case 'int':
				return intval($value);
			case 'float':
				return floatval($value);
			case 'array':
				return @unserialize($value);
			case 'json':
				return @json_decode($value, true);
			case 'date':
				return $value ? date('Y-m-d', $value) : '';
			case 'datetime':
				return $value ? date('Y-m-d H:i:s', $value) : '';
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
	protected function pack($field, $value, $options) {
		switch ($options['type']) {
			case 'int':
				return intval($value);
			case 'float':
				return floatval($value);
			case 'array':
				return @serialize($value);
			case 'json':
				return @json_encode($value);
			case 'bool':
				return in_array(strtolower($value), ['on', 'yes', '1', 'enabled']) ? 1 : 0;
			case 'datetime':
			case 'date':
				return strtotime($value);
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
	 * 1. type 数据类型, int,float,number,string,bool,array,json,date,datetime等.
	 * 2. skip 不更新到数据表字段
	 * 3. name 字段名,如果不指定则与属性同名.
	 * 4. 验证注解，见Validator.
	 * 5. 第三方插件支持的注解.
	 * 6. var  为IFormField类型
	 * 7. typef 当type值为number时用来定义number_format参数.
	 */
	private function parseFields() {
		$this->_fields   = [];
		$refobj          = new \ReflectionObject($this);
		$dann            = new Annotation($refobj);
		$this->_xssClean = $dann->getBool('xssclean', true);
		unset($dann);
		$fields = $refobj->getProperties(\ReflectionProperty::IS_PUBLIC);
		if (!empty($fields)) {
			$sfields = [];
			/**@var  \ReflectionProperty $field */
			foreach ($fields as $field) {
				$ann = new Annotation($field);
				if ($ann->has('type')) {
					//表单名
					$fname     = $field->getName();
					$sfields[] = $this->addField($fname, $ann, $this->{$fname});
				}
			}
			if ($sfields) {
				$this->defaultQueryFields = implode(',', $sfields);
			}
		}
		fire(get_class($this) . '::onParseFields', $this);
	}

	/**
	 * 排除字段(不会绘制)
	 *
	 * @param array ...$fields
	 */
	public function excludeFields(...$fields) {
		foreach ($fields as $field) {
			$this->_excludes[ $field ] = 1;
		}
	}

	/**
	 * 添加字段.
	 *
	 * @param string                   $fname
	 * @param \wulaphp\util\Annotation $ann
	 * @param string                   $default
	 *
	 * @return string 字段名
	 */
	protected function addField($fname, Annotation $ann, $default) {
		if ($ann->has('type')) {
			//字段名
			$fieldName = $ann->getString('name', $fname);
			//忽略值
			$this->_skips_[ $fieldName ] = $ann->has('skip');
			//字段配置
			$this->_fields[ $fname ] = [
				'annotation' => $ann,
				'var'        => $ann->getString('var', ''),
				'name'       => $fieldName,
				'default'    => $default,
				'type'       => $ann->getString('type', 'string'),
				'typef'      => $ann->getString('typef'),
				'xssClean'   => $ann->getBool('xssclean', $this->_xssClean)
			];
			//映射
			$this->_maps_[ $fieldName ] = $fname;

			return "`{$fieldName}`";
		}

		return null;
	}

	/**
	 * 创建组件.
	 *
	 * @return array|null 组件列表.
	 */
	public final function createWidgets() {
		if ($this->_widgets !== null) {
			return $this->_widgets;
		}
		$this->_widgets = [];
		foreach ($this->_fields as $key => $field) {
			if (isset($this->_excludes[ $key ])) {
				continue;
			}
			$cls = $field['var'];
			if ($cls && is_subclass_of($cls, FormField::class)) {
				/**@var \wulaphp\form\FormField $clz */
				$clz       = new $cls($key, $this, $field);
				$fieldName = $field['name'];
				if (isset($this->_tableData[ $fieldName ])) {
					$clz->setValue($this->_tableData[ $fieldName ]);
				} else {
					$clz->setValue($field['default']);
				}
				$this->_widgets[ $key ] = $clz;
			}
		}

		return $this->_widgets;
	}
}