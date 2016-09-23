<?php
namespace wulaphp\mvc\controller;

use wulaphp\io\Session;

abstract class SessionController extends Controller {

	public function __construct($module) {
		parent::__construct($module);
		(new Session ())->start();
	}
}