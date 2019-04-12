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

class LocaleUploader implements IUploader {
    protected $last_error       = '';
    protected $upload_root_path = '';
    protected $filename;

    /**
     * LocaleUploader constructor.
     *
     * @param string|null $path     存储路径,不指定时存储到WWWROOT目录
     * @param string|null $filename 存储为固定文件
     */
    public function __construct($path = null, $filename = null) {
        if (empty ($path)) {
            $this->upload_root_path = WEB_ROOT;
        } else {
            $this->upload_root_path = $path;
        }
        $this->filename = $filename;
    }

    public function getName() {
        return '本地文件上传器';
    }

    /**
     * 默认文件上传器.
     *
     * @param string $filepath 文件路径
     * @param string $path     目的目录
     *
     * @return array|bool  成功返回关键数组:
     * url,name,path
     */
    public function save($filepath, $path = null) {
        $path = trailingslashit($this->getDestDir($path));

        $destdir  = trailingslashit($this->upload_root_path) . $path;
        $tmp_file = $filepath;

        if (!is_dir($destdir) && !@mkdir($destdir, 0777, true)) { // 目的目录不存在，且创建也失败
            $this->last_error = '无法创建目录[' . $destdir . ']';

            return false;
        }
        $pathinfo = pathinfo($tmp_file);
        $fext     = '.' . strtolower($pathinfo ['extension']);
        if ($this->filename) {
            $name = $this->filename . $fext;
        } else {
            $name = $pathinfo ['filename'] . $fext;
            $name = unique_filename($destdir, $name);
        }
        $fileName = $path . $name;
        $destfile = $destdir . $name;
        $result   = @copy($tmp_file, $destfile);
        if ($result == false) {
            $this->last_error = '无法将文件[' . $tmp_file . ']重命名为[' . $destfile . ']';

            return false;
        }
        $fileName = str_replace(DS, '/', $fileName);

        return ['url' => $fileName, 'name' => $pathinfo ['basename'], 'path' => $fileName];
    }

    public function get_last_error() {
        return $this->last_error;
    }

    public function delete($file) {
        $file = $this->upload_root_path . $file;
        if (file_exists($file)) {
            @unlink($file);
        }

        return true;
    }

    public function close() {
        // nothing to do.
    }

    public function thumbnail($file, $w, $h) {
    }

    public function configHint() {
        return '';
    }

    public function configValidate($config) {
        return true;
    }

    /**
     * @param string $path 如果以@开头则不添加upload_dir@media配置.
     *
     * @return string
     */
    public function getDestDir($path = null) {
        if (!$path) {
            $dir = App::icfg('dir@media', App::icfg('upload.dir', 1));
            switch ($dir) {
                case 0:
                    $path = date('/Y/');
                    break;
                case 1:
                    $path = date('/Y/n/');
                    break;
                default:
                    $path = date('/Y/n/d/');
            }
            $rand_cnt = App::icfg('group_num@media', App::icfg('upload.group', 0));
            if ($rand_cnt > 1) {
                $cnt  = rand(0, $rand_cnt - 1);
                $path .= $cnt . '/';
            }
        }
        $uploadPath = App::cfg('upload.path', 'files');
        if ($path{0} == '@') {
            return substr($path, 1);
        } else if ($path{0} == '~') {
            $save_path = App::cfg('save_path@media', $uploadPath);
            if (!$save_path) {
                $save_path = 'files';
            }

            return trailingslashit($save_path) . substr($path, 1);
        } else {
            $save_path = App::cfg('save_path@media', $uploadPath);
            if (!$save_path) {
                $save_path = 'files';
            }

            return $save_path . $path;
        }
    }
}