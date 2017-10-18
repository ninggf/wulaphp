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

class LocaleUploader implements IUploader {
	protected $last_error       = '';
	protected $upload_root_path = '';

	public function __construct($path = null) {
		if (empty ($path)) {
			$this->upload_root_path = WEB_ROOT;
		} else {
			$this->upload_root_path = $path;
		}
	}

	/**
	 * 默认文件上传器.
	 *
	 * @param string $filepath
	 * @param string $path
	 *
	 * @return array|bool
	 */
	public function save($filepath, $path = null) {
		$path = $this->getDestDir($path);

		$destdir  = trailingslashit(trailingslashit($this->upload_root_path) . $path);
		$tmp_file = $filepath;

		if (!is_dir($destdir) && !@mkdir($destdir, 0777, true)) { // 目的目录不存在，且创建也失败
			$this->last_error = '无法创建目录[' . $destdir . ']';

			return false;
		}
		$pathinfo = pathinfo($tmp_file);
		$fext     = '.' . strtolower($pathinfo ['extension']);
		$name     = $pathinfo ['filename'] . $fext;
		$name     = unique_filename($destdir, $name);
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

	/**
	 * @param string $path 如果以@开头则不添加upload_dir@media配置.
	 *
	 * @return string
	 */
	public function getDestDir($path = null) {
		if (!$path) {
			$path     = date('/Y/n/');
			$rand_cnt = 0;
			if ($rand_cnt > 1) {
				$cnt  = rand(0, $rand_cnt - 1);
				$path .= $cnt . '/';
			}
		}
		if ($path{0} == '@') {
			return substr($path, 1);
		} else {
			return 'files' . $path;
		}
	}
}