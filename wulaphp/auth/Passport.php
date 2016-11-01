<?php
namespace wulaphp\auth;

abstract class Passport implements \Serializable {

	public function serialize() {

	}

	public function unserialize($serialized) {
	}

	public function save() {

	}

	public function auth() {

	}

	public function getRoles() {
		return [];
	}
}