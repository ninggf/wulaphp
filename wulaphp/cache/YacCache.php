<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\cache;
/**
 * Class YacCache
 * @package wulaphp\cache
 * @internal
 */
class YacCache extends Cache {
	private $yac;

	public function getName() {
		return 'Yac';
	}

	public function __construct($prefix = '') {
		$this->yac = new \Yac($prefix);
	}

	public function add($key, $data, $expire = 0) {
		$this->yac->set($key, $data);

		return true;
	}

	public function delete($key) {
		$this->yac->delete($key);

		return true;
	}

	public function get($key) {
		return $this->yac->get($key);
	}

	public function clear($check = true) {
		$this->yac->flush();

		return true;
	}

	public function has_key($key) {
		return $this->yac->get($key) !== false;
	}
}