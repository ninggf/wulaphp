<?php

namespace wulaphp\auth;
/**
 * Acl 资源.
 *
 * @package wulaphp\auth
 */
class AclResource implements \ArrayAccess {
	private $uri;
	private $id;
	private $operations = array();
	private $note;
	private $name;
	private $items      = array();
	private $defaultOp  = 'm';
	private $resId      = '';

	public function __construct($id, $uri = '', $name = '') {
		$this->id  = $id;
		$this->uri = empty ($uri) ? $id : $uri;
		if ($name) {
			$this->name = $name;
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getNote() {
		return $this->note;
	}

	public function getNodes() {
		return $this->items;
	}

	public function getURI() {
		return $this->uri;
	}

	public function getNode($id, $uri) {
		foreach ($this->items as $node) {
			if ($id == $node->getId()) {
				return $node;
			}
		}
		$node           = new AclResource ($id, $uri);
		$this->items [] = $node;

		return $node;
	}

	public function getOperations() {
		return $this->operations;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setNote($note) {
		$this->note = $note;
	}

	public function addOperate($op, $name, $extra_url = '', $default = false) {
		if ($default) {
			$this->defaultOp = '*';
			$this->resId     = '*:' . $this->uri;
		} else {
			$this->operations [ $op ] = array('uri' => $this->uri, 'name' => $name, 'extra' => $extra_url, 'resId' => $op . ':' . $this->uri);
		}
	}

	public function offsetExists($offset) {
		return isset ($this->{$offset});
	}

	public function offsetGet($offset) {
		return $this->{$offset};
	}

	public function offsetSet($offset, $value) {
	}

	public function offsetUnset($offset) {
	}
}