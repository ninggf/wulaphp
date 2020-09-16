<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util\data;

/**
 * 基于文件的数据源管理器.
 *
 * @package wulaphp\util\data
 */
class FileState extends State {
    private $fileName;

    /**
     * FileState constructor.
     *
     * @param string $name 管理器的名称，会做为文件名的一部分.
     *
     * @throws \InvalidArgumentException when name is empty
     */
    public function __construct(string $name) {
        parent::__construct($name);
        $this->fileName = TMP_PATH . '.dstate_' . sanitize_file_name($name) . '.json';
    }

    public function get() {
        if (is_file($this->fileName)) {
            $state = @file_get_contents($this->fileName);
            if ($state) {
                return @json_decode($state) ?? [];
            }
        }

        return [];
    }

    public function save(array $state) {
        if (empty($state)) {
            @unlink($this->fileName);

            return true;
        }
        $state = json_encode($state);

        return @file_put_contents($this->fileName, $state) > 0;
    }
}