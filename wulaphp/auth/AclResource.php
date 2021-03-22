<?php

namespace wulaphp\auth;
/**
 * Acl 资源.
 *
 * @package wulaphp\auth
 * @property-read string $id
 * @property-read string $uri
 * @property-read string $name
 * @property-read string $resId
 * @property-read string $note
 * @property-read string $defaultOp
 * @property-read array  $operations
 * @property-read array  $items
 */
class AclResource implements \ArrayAccess {
    private $uri;
    private $id;
    private $operations = [];
    private $note;
    private $name;
    private $items      = [];

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
        $ops = [];
        foreach ($this->operations as $op) {
            $ops[ $op['op'] ] = $op;
        }

        return $ops;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setNote($note) {
        $this->note = $note;
    }

    public function addOperate($op, $name, $extra_url = '') {
        static $idx = 0, $ops = [];
        if (isset($ops[ $op ]))
            return;
        $ops [ $op ]                  = true;
        $this->operations [ $idx ++ ] = [
            'op'    => $op,
            'uri'   => $this->uri,
            'name'  => $name,
            'extra' => $extra_url,
            'resId' => $op . ':' . $this->uri
        ];
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

    public function __get($name) {
        return $this->offsetGet($name);
    }
}