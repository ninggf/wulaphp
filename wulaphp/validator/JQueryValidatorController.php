<?php
/**
 *
 * User: Leo Ning.
 * Date: 2017/7/12 0012 下午 1:16
 */

namespace wulaphp\validator;

use wulaphp\form\FormTable;
use wulaphp\util\Annotation;

/**
 * Trait JQueryValidatorController
 * @package wulaphp\validator
 * @property $reflectionObj
 */
trait JQueryValidatorController {
	/**
	 * 远程验证表单.
	 *
	 * @param string $_arg0_form
	 * @param string $_arg1_filed
	 *
	 * @return string
	 */
	public function validate($_arg0_form, $_arg1_filed) {
		if (isset($this->reflectionObj) && $this->reflectionObj) {
			$ann     = new Annotation($this->reflectionObj);
			$accepts = $ann->getMultiValues('accept');
			if (empty($accepts)) {
				return 'false';
			}
			$_arg0_form = ltrim(str_replace('.', '\\', $_arg0_form), '\\');
			if ($_arg0_form && ($accepts[0] == '*' || in_array($_arg0_form, $accepts)) && is_subclass_of($_arg0_form, FormTable::class)) {
				/**@var \wulaphp\form\FormTable $form */
				$form = new $_arg0_form(true);
				$data = $form->formData();
				try {
					$form->validate($data, $form->getValidateRules($_arg1_filed));

					return 'true';
				} catch (ValidateException $e) {
					return "false";
				}
			}
		}

		return "false";
	}
}