<?php
/**
 *
 * User: Leo Ning.
 * Date: 2017/7/12 0012 下午 1:16
 */

namespace wulaphp\validator;

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
			$ann        = new Annotation($this->reflectionObj);
			$accepts    = $ann->getArray('accept');
			$_arg0_form = ltrim(str_replace('.', '\\', $_arg0_form), '\\');
			if (class_exists($_arg0_form) && in_array($_arg0_form, $accepts)) {
				/**@var \wulaphp\form\FormTable $form */
				$form = new $_arg0_form();
				$data = $form->inflate();
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