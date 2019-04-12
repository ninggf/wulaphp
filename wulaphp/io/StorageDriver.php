<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\io;
/**
 * 存储驱动蕨类
 * @package wulaphp\io
 */
abstract class StorageDriver {
    protected $options;

    /**
     * StorageDriver constructor.
     *
     * @param string $ssn 类似PDO的DSN字符串
     *
     * @throws \Exception
     */
    public function __construct($ssn) {
        $ssn           = str_replace(';', "\n", $ssn);
        $this->options = @parse_ini_string($ssn);
        if (!$this->initialize()) {
            throw_exception('cannot initialize storage: ' . $ssn);
        }
    }

    /**
     * 初始化存储器.
     *
     * @return bool 初始化存储器成功返回true.
     */
    protected function initialize() {
        return true;
    }

    /**
     * 保存.
     *
     * @param string $filename 文件名
     * @param string $content  内容
     *
     * @return bool
     */
    public abstract function save($filename, $content);

    /**
     * 加载文件正文.
     *
     * @param string $filename 文件名
     *
     * @return string
     */
    public abstract function load($filename);

    /**
     * 删除文件.
     *
     * @param string $filename
     *
     * @return bool
     */
    public abstract function delete($filename);
}