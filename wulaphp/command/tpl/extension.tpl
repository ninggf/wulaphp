<?php

namespace {$namespace};

use wulaphp\app\App;
use wulaphp\app\Extension;

class {$extension}Extension extends Extension {
	public function getName() {
		// TODO: Implement getName() method.
		return '';
	}
}

App::registerExtension(new {$extension}Extension());