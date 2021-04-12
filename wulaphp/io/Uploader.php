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

use wulaphp\app\App;

/**
 * 文件上传器基类.
 *
 * @package wulaphp\io
 */
abstract class Uploader implements IUploader {
    protected $error = null;

    public function get_last_error(): ?string {
        return $this->error;
    }

    public function thumbnail(string $file, int $w, int $h) {
        return null;
    }

    public function close(): bool {
        return true;
    }

    public function configHint(): string {
        return '';
    }

    /**
     * 获取文件上传器.
     *
     * @param string|null $id 上传器ID
     * @param null        $config
     *
     * @return \wulaphp\io\IUploader|null
     */
    public static function getUploader(?string $id = null, $config = null): ?IUploader {
        if (!$id) {
            $id = App::cfg('upload.uploader', 'file');
        }
        $uploaders = self::uploaders();
        if (isset($uploaders[ $id ])) {
            $uploaderCls = $uploaders[ $id ];
            if ($uploaderCls instanceof IUploader) {
                $uploader = new $uploaderCls();
                $uploader->setup($config);
            } else {
                $uploader = null;
            }
        } else {
            $uploader = new LocaleUploader();
        }
        if ($uploader) {
            $uploader->setup($config);
        }

        return apply_filter('upload\getUploader', $uploader);
    }

    /**
     * 系统可用文件上传器.
     *
     * @return string[]
     */
    public static function uploaders(): array {
        static $uploaders = [];
        if (!$uploaders) {
            $uploaders['file'] = LocaleUploader::class;
            if (extension_loaded('ftp')) {
                $uploaders['ftp'] = FtpUploader::class;
            }
            $uploaders = apply_filter('upload\regUploaders', $uploaders);
        }

        return $uploaders;
    }
}