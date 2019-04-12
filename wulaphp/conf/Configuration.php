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

    public function setConfigs($settings) {
        if ($settings instanceof Configuration) {
            $this->settings = array_merge($this->settings, $settings->settings);
        } else if ($settings && is_array($settings)) {
            $this->settings = array_merge($this->settings, $settings);
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
     * @param string $name 支持.分多级配置
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name, $default = '') {
        $setting = &$this->settings;
        $names   = explode('.', $name);
        $lname   = array_pop($names);
        if ($names) {
            foreach ($names as $n) {
                if (!isset($setting[ $n ])) {
                    $setting[ $n ] = [];
                }
                $setting = &$setting[ $n ];
            }
        }

        return isset($setting [ $lname ]) ? $setting [ $lname ] : $default;
    }

    /**
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function getb($name, $default = false) {
        $v = $this->get($name, $default);

        return (bool)$v;
    }

    /**
     * @param string $name
     * @param int    $default
     *
     * @return int
     */
    public function geti($name, $default = 0) {
        $v = $this->get($name, $default);

        return (int)$v;
    }

    /**
     * @param string $name
     * @param array  $default
     *
     * @return array
     */
    public function geta($name, array $default = []) {
        return (array)$this->get($name, $default);
    }

    /**
     * 设置.
     *
     * @param string $name 支持通过'.'进行多级设置。
     * @param mixed  $value
     */
    public function set($name, $value) {
        $setting = &$this->settings;
        $names   = explode('.', $name);
        $lname   = array_pop($names);
        if ($names) {
            foreach ($names as $n) {
                if (!isset($setting[ $n ])) {
                    $setting[ $n ] = [];
                }
                $setting = &$setting[ $n ];
            }
        }
        $setting[ $lname ] = $value;
    }

    /**
     * 设置，但允许通过配置文件重写
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setm($name, $value) {
        if (is_array($value)) {
            $ov    = $this->geta($name);
            $value = array_merge($value, $ov);
        }
        $this->set($name, $value);
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