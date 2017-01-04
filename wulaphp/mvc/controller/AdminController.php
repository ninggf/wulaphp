<?php
/*
 * 启用了SessionSupport, PassportSupport, RbacSupport特性的控制器.
 *
 * 通行证类型为admin，请根据需要提供相应的通行证实现.
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\controller;

use wulaphp\auth\PassportSupport;
use wulaphp\auth\RbacSupport;

class AdminController extends Controller {
	use SessionSupport, PassportSupport, RbacSupport;
	protected $passportType = 'admin';

	public function __construct(\wulaphp\app\Module $module) {
		parent::__construct($module);
		$this->globalRbacSetting['login'] = true;
	}

	protected function needLogin() {
		fire('mvc\admin\needLogin');
	}

	protected function onDenied($message) {
		fire('mvc\admin\onDenied', $message);
	}
}