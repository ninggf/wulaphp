<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\form\providor;

class ParamDataProvidor extends FieldDataProvidor {
	public function getData($search = false) {
		if ($this->option) {
			@parse_str($this->option, $data);

			return $data ? $data : [];
		}

		return [];
	}
}