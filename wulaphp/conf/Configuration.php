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

    public function __construct(string $name = 'default') {
        $this->name = $name;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function name(): string {
        return $this->name;
    }

    public function setConfigs($settings) {
        if (empty($this->settings)) {
            $this->settings = $settings instanceof Configuration ? $settings->settings : $settings;

            return;
        }
        if ($settings instanceof Configuration) {
            ary_deep_merge($this->settings, $settings->settings);
        } else if ($settings && is_array($settings)) {
            ary_deep_merge($this->settings, $settings);
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
    public function get(string $name, $default = '') {
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

        return $setting [ $lname ] ?? $default;
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
     * 获取`array`配置值.
     *
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
     * @param string $name 支持通过'.'进行多级设置。
     * @param mixed  $value
     */
    public function set(string $name, $value) {
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
    public function setm(string $name, $value) {
        if (is_array($value)) {
            $ov    = $this->geta($name);
            $value = array_merge($value, $ov);
        }
        $this->set($name, $value);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator {
        return new \ArrayIterator ($this->settings);
    }

    public function toArray(): array {
        return $this->settings;
    }
}