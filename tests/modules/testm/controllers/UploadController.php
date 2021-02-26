<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\controllers;

use wulaphp\io\LocaleUploader;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\controller\UploadSupport;

class UploadController extends Controller {
    use UploadSupport;

    public function index() {
        $uploader = new LocaleUploader(TMP_PATH, 'test');
        $rst      = $this->upload('@abc', 1000000, true, $uploader, null, ['txt']);

        return is_array($rst) ? $rst : 'error';
    }
}