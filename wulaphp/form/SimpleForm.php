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

use wulaphp\util\TraitObject;

/**
 * 简单表单.
 *
 * @package wulaphp\form
 */
abstract class SimpleForm extends TraitObject implements IForm {
    use Form;
}