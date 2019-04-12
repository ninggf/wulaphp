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

interface IUploader {
    /**
     * @return string
     */
    public function getName();

    /**
     * 上传文件.
     *
     * @param string $filepath 要上传的文件路径.
     * @param string $path     存储路径,如果是null则自系统自动生成.
     *
     * @return array array(url,name,path,width,height,size)
     *         <code>
     *         <ul>
     *         <li>url-相对URL路径</li>
     *         <li>name-文件名</li>
     *         <li>path-存储路径</li>
     *         </ul>
     *         </code>
     */
    public function save($filepath, $path = null);

    /**
     * 返回错误信息.
     */
    public function get_last_error();

    /**
     * delete the file.
     *
     * @param string $file
     *            要删除的文件路径.
     *
     * @return boolean 成功返回true,反之返回false.
     */
    public function delete($file);

    /**
     * 生成缩略图.
     *
     * @param string $file
     * @param int    $w
     * @param int    $h
     *
     * @return mixed
     */
    public function thumbnail($file, $w, $h);

    /**
     * close connection if there is.
     */
    public function close();

    /**
     * 配置提示
     * @return string
     */
    public function configHint();

    public function configValidate($config);
}