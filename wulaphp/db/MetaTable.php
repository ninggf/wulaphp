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
 * @property-read string $metaIdField
 * @property-read string $metaNameField
 * @property-read string $originTable
 * @method \wulaphp\db\sql\Query get($id, $fields = '*')
 */
trait MetaTable {
    protected final function onInitMetaTable() {
        if (!isset($this->metaIdField) || !$this->metaIdField) {
            $this->metaIdField = strtolower(preg_replace('/_meta$/', '', $this->originTable)) . '_id';
        }
        if (!isset($this->metaNameField) || !$this->metaNameField) {
            $this->metaNameField = 'name';
        }
    }

    /**
     * 设置用户字符型元数据.
     *
     * @param int          $id
     * @param string       $name
     * @param string|array $value
     * @param string       $field
     *
     * @return bool
     */
    public function setMeta(int $id, string $name, $value, string $field = 'value'): bool {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->updateMeta($id, $name, $field, $value);
    }

    /**
     * 批量设置元数据.
     *
     * @param int    $id
     * @param array  $metas [name=>value]
     * @param string $field
     *
     * @return bool
     */
    public function setMetas(int $id, array $metas, string $field = 'value'): bool {
        $update = [$field => imv("VALUES(`$field`)")];
        $datas  = [];
        foreach ($metas as $name => $value) {
            $data[ $this->metaIdField ]   = $id;
            $data[ $this->metaNameField ] = $name;
            $data[ $field ]               = $value;
            $datas[]                      = $data;
        }

        return $this->upserts($datas, $update, 'UDX_ID_NAME');
    }

    /**
     * 获取JSON
     *
     * @param int    $id
     * @param string $name
     * @param string $field
     *
     * @return array
     */
    public function getJsonMeta(int $id, string $name, string $field = 'value'): array {
        $values = $this->get([$this->metaIdField => $id, $this->metaNameField => $name])->get($field);
        if ($values) {
            $values = @json_decode($values, true);
        }

        return $values ? $values : [];
    }

    /**
     * 取字符.
     *
     * @param int         $id
     * @param string|null $name
     * @param string      $field
     *
     * @return array|string
     */
    public function getMeta(int $id, ?string $name = null, string $field = 'value') {
        if ($name) {
            $values = $this->get([$this->metaIdField => intval($id), $this->metaNameField => $name])->get($field);
        } else {
            $values = $this->findAll([$this->metaIdField => intval($id)], [
                $field,
                $this->metaNameField
            ])->toArray($field, $this->metaNameField);
        }

        return $values;
    }

    /**
     * 设置元数据.
     *
     * @param int        $id
     * @param string     $name
     * @param string     $field
     * @param string|int $value
     *
     * @return bool
     */
    private function updateMeta(int $id, string $name, string $field, $value): bool {
        $data[ $this->metaIdField ]   = $id;
        $data[ $this->metaNameField ] = $name;
        $data[ $field ]               = $value;
        try {
            return $this->upsert($data, [$field => $value], 'UDX_ID_NAME') !== false;
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
        }

        return false;
    }
}