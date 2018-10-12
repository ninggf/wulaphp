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
 * 文件上传器基类.
 *
 * @package wulaphp\io
 */
abstract class Uploader implements IUploader {
    protected $error = null;

    public function get_last_error() {
        return $this->error;
    }

    public function thumbnail($file, $w, $h) {
        return null;
    }

    public function close() {
        return true;
    }

    /**
     * 获取文件上传器.
     *
     * @param string|null $id 上传器ID
     *
     * @return \wulaphp\io\IUploader|null
     */
    public static function getUploader($id = null) {
        if ($id) {
            $uploaders = self::uploaders();
            if (isset($uploaders[ $id ])) {
                return $uploaders[ $id ];
            }

            return null;
        } else {
            return apply_filter('upload\getUploader', new LocaleUploader());
        }
    }

    /**
     * 系统可用文件上传器.
     *
     * @return \wulaphp\io\IUploader[]
     */
    public static function uploaders() {
        static $uploaders = [];
        if (!$uploaders) {
            $uploaders['file'] = new LocaleUploader();
            $uploaders         = apply_filter('upload\regUploaders', $uploaders);
        }

        return $uploaders;
    }
}