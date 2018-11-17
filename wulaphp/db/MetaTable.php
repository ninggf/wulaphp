<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\db;

/**
 * Trait MetaTable
 * @package wulaphp\db
 * @property-read $metaIdField
 * @property-read $metaNameField
 */
trait MetaTable {
    protected function onInitMetaTable() {
        if (!isset($this->metaIdField) || !$this->metaIdField) {
            $this->metaIdField = strtolower(preg_replace('/_meta$/', '', $this->originTable)) . '_id';
        }
        if (!isset($this->metaNameField) || !$this->metaNameField) {
            $this->metaIdField = 'name';
        }
    }

    /**
     * 设置用户字符型元数据.
     *
     * @param string|int   $id
     * @param string       $name
     * @param string|array $value
     * @param string       $field
     *
     * @return bool
     */
    public function setMeta($id, $name, $value, $field = 'value') {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return $this->updateMeta($id, $name, $field, $value);
    }

    /**
     * 获取JSON
     *
     * @param string|int $id
     * @param string     $name
     * @param string     $field
     *
     * @return array
     */
    public function getJsonMeta($id, $name, $field = 'value') {
        $values = $this->get([$this->metaIdField => intval($id), $this->metaNameField => $name])->get($field);
        if ($values) {
            $values = @json_decode($values, true);
        }

        return $values ? $values : [];
    }

    /**
     * 取字符.
     *
     * @param string|int  $id
     * @param string|null $name
     * @param string      $field
     *
     * @return array|string
     */
    public function getMeta($id, $name = null, $field = 'value') {
        if ($name) {
            $values = $this->get([$this->metaIdField => intval($id), $this->metaNameField => $name])->get($field);
        } else {
            $values = $this->findAll([$this->metaIdField => intval($id)])->toArray($field, $this->metaNameField);
        }

        return $values;
    }

    /**
     * 设置元数据.
     *
     * @param string|int $uid
     * @param string     $name
     * @param string     $field
     * @param string|int $value
     *
     * @return bool
     */
    private function updateMeta($uid, $name, $field, $value) {
        $w[ $this->metaNameField ] = $name;
        $w[ $this->metaIdField ]   = $uid;
        $data[ $field ]            = $value;
        try {
            if ($this->exist($w)) {
                return $this->update($data, $w);
            } else {
                $w[ $field ] = $data[ $field ];

                return $this->insert($w);
            }
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
        }

        return false;
    }
}