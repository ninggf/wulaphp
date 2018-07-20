<?php

namespace wulaphp\conf;

/**
 * 系统配置类.
 *
 * @author leo
 *
 */
class Configuration implements \ArrayAccess, \IteratorAggregate {

	protected $settings = [];

	protected $name;

	public function __construct($name = 'default') {
		$this->name = $name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function name() {
		return $this->name;
	}

	public function setConfigs($settings = []) {
		if ($settings) {
			$this->settings = array_merge($this->settings, (array)$settings);
		}
	}

	public function offsetExists($offset) {
		return isset ($this->settings [ $offset ]);
	}

	public function offsetGet($offset) {
		return $this->get($offset, null);
	}

	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	public function offsetUnset($offset) {
		unset ($this->settings [ $offset ]);
	}

	/**
	 * 获取设置.
	 *
	 * @param string $name
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get(string $name, $default = '') {
		return $this->settings [ $name ] ?? $default;
	}

	/**
	 * @param string $name
	 * @param bool   $default
	 *
	 * @return bool
	 */
	public function getb(string $name, bool $default = false): bool {
		$v = $this->get($name, $default);

		return (bool)$v;
	}

	/**
	 * @param string $name
	 * @param int    $default
	 *
	 * @return int
	 */
	public function geti(string $name, int $default = 0): int {
		$v = $this->get($name, $default);

		return (int)$v;
	}

	/**
	 * @param string $name
	 * @param array  $default
	 *
	 * @return array
	 */
	public function geta(string $name, array $default = []): array {
		return (array)$this->get($name, $default);
	}

	/**
	 * 设置.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function set($name, $value) {
		$this->settings [ $name ] = $value;
	}

	/*
	 * (non-PHPdoc) @see IteratorAggregate::getIterator()
	 */
	public function getIterator() {
		return new \ArrayIterator ($this->settings);
	}

	public function toArray() {
		return $this->settings;
	}
}