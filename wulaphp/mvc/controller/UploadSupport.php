<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\controller;

use wulaphp\app\App;
use wulaphp\io\Uploader;
use wulaphp\util\ImageTool;

trait UploadSupport {
    /**
     * 保存通过Plupload上传的文件.
     *
     * @param string|null           $dest            目标目录
     * @param int                   $maxSize         最大上传体积
     * @param bool                  $canUpload       是否可以上传
     * @param \wulaphp\io\IUploader $uploader        使用指定文件上传器.
     * @param \Closure              $checkResolution 上传之前的回调
     *
     * @return array
     */
    protected final function upload($dest = null, $maxSize = 10000000, $canUpload = true, $uploader = null, $checkResolution = null) {
        $rtn = ['jsonrpc' => '2.0', 'done' => 0];
        if (!$canUpload) {
            $rtn['error'] = ['code' => 422, 'message' => '无权限上传文件'];

            return $rtn;
        }

        $chunk     = irqst('chunk');
        $chunks    = irqst('chunks');
        $name      = rqst('name');
        $hasWater  = !rqset('nowater');
        $targetDir = TMP_PATH . "plupload";
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        if (!is_dir($targetDir)) {
            $rtn['error'] = ['code' => 422, 'message' => '临时目录不存在，无法上传.'];

            return $rtn;
        }
        $cleanupTargetDir = true;
        $maxFileAge       = 1080000;
        @set_time_limit(0);
        // Clean the fileName for security reasons
        if (empty ($name)) {
            $name = isset ($_FILES ['file'] ['name']) ? $_FILES ['file'] ['name'] : false;
        }
        if (empty ($name)) {
            $rtn['error'] = ['code' => 422, 'message' => '文件名为空'];

            return $rtn;
        }
        $name     = thefilename($name);
        $fileName = preg_replace('/[^\w\._]+/', rand_str(5, 'a-z'), $name);
        $filext   = strtolower(strrchr($fileName, '.'));

        if (!$this->allowed($filext)) {
            $rtn['error'] = ['code' => 422, 'message' => '不允许的文件扩展名'];

            return $rtn;
        }
        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2 && file_exists($targetDir . DS . $fileName)) {
            $fileName = unique_filename($targetDir, $fileName);
        }

        $filePath = $targetDir . DS . $fileName;
        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Remove old temp files
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = @opendir($targetDir))) {
            while (($file = @readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DS . $file;
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }
            @closedir($dir);
        } else {
            $rtn['error'] = ['code' => 422, 'message' => '无法打开临时目录'];

            return $rtn;
        }
        $contentType = '';
        // Look for the content type header
        if (isset ($_SERVER ["HTTP_CONTENT_TYPE"])) {
            $contentType = $_SERVER ["HTTP_CONTENT_TYPE"];
        }
        if (isset ($_SERVER ["CONTENT_TYPE"])) {
            $contentType = $_SERVER ["CONTENT_TYPE"];
        }
        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (!empty ($_FILES ['file'] ['error'])) {
                switch ($_FILES ['file'] ['error']) {
                    case '1' :
                        $error = '超过php.ini允许的大小。';
                        break;
                    case '2' :
                        $error = '超过表单允许的大小。';
                        break;
                    case '3' :
                        $error = '图片只有部分被上传。';
                        break;
                    case '4' :
                        $error = '请选择图片。';
                        break;
                    case '6' :
                        $error = '找不到临时目录。';
                        break;
                    case '7' :
                        $error = '写文件到硬盘出错。';
                        break;
                    case '8' :
                        $error = 'File upload stopped by extension。';
                        break;
                    case '999' :
                    default :
                        $error = '未知错误。';
                }
                $rtn['error'] = ['code' => 422, 'message' => $error];

                return $rtn;
            }

            if (isset ($_FILES ['file'] ['tmp_name']) && is_uploaded_file($_FILES ['file'] ['tmp_name'])) {
                if ($chunks == 1) {//直接上传
                    if (!@move_uploaded_file($_FILES['file']['tmp_name'], "{$filePath}.part")) {
                        $rtn['error'] = ['code' => 422, 'message' => '系统错误，无法保存临时文件'];
                    }
                } else {//分片上传
                    $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                    if ($out) {
                        $in = @fopen($_FILES ['file'] ['tmp_name'], "rb");
                        if ($in) {
                            do {
                                $buff = @fread($in, 4096);
                                if ($buff) {
                                    @fwrite($out, $buff);
                                }
                            } while ($buff);

                        } else {
                            $rtn['error'] = ['code' => 422, 'message' => '系统错误，无法打开输入流'];
                        }
                        @fclose($out);
                        @fclose($in);
                        @unlink($_FILES ['file'] ['tmp_name']);
                    } else {
                        $rtn['error'] = ['code' => 422, 'message' => '系统错误，无法保存临时文件'];
                    }
                }
            } else {
                $rtn['error'] = ['code' => 422, 'message' => '系统错误，尝试打开上传的文件错误'];
            }
        } else {//通过php://input流上传
            $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = @fopen("php://input", "rb");
                if ($in) {
                    do {
                        $buff = fread($in, 4096);
                        if ($buff) fwrite($out, $buff);
                    } while ($buff);
                } else {
                    $rtn['error'] = ['code' => 422, 'message' => '系统错误，无法打开输入流'];
                }
                @fclose($in);
                @fclose($out);
            } else {
                $rtn['error'] = ['code' => 422, 'message' => '系统错误，无法保存临时文件'];
            }
        }
        //是否上传失败
        if (isset($rtn['error'])) {
            return $rtn;
        }
        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            if (@rename("{$filePath}.part", $filePath)) {
                $fsize = filesize($filePath);
                if ($fsize > $maxSize) {
                    @unlink($filePath);
                    $rtn['error'] = ['code' => 422, 'message' => '文件太大'];

                    return $rtn;
                }

                $imgwh = ['width' => 0, 'height' => 0];
                if (ImageTool::isImage($filePath)) {
                    if (($imgData = @getimagesize($filePath))) {
                        $imgwh['width']  = $imgData[0];
                        $imgwh['height'] = $imgData[1];
                    }
                    //添加水印
                    if ($hasWater && ($water = $this->watermark())) {
                        $img = new ImageTool($filePath);
                        $img->watermark($water, App::cfg('watermark_pos@media', 'br'), App::cfg('watermark_min_size@media'));
                        unset($img);
                    }
                }
                if ($checkResolution instanceof \Closure && ($chkRst = $checkResolution(...$imgwh)) !== true) {
                    $rtn['error'] = ['code' => 423, 'message' => $chkRst ? $chkRst : '图片尺寸不正确'];
                    @unlink($filePath);

                    return $rtn;
                }
                $uploader = $uploader ? $uploader : Uploader::getUploader();
                if ($uploader) {
                    try {
                        $rst = $uploader->save($filePath, $dest);
                    } catch (\Exception $e) {
                        $rst = false;
                    }
                    if ($rst) {
                        $rst['size']   = $fsize;
                        $rtn['result'] = $rst;
                        $rtn['done']   = 1;
                        $rtn['width']  = $imgwh['width'];
                        $rtn['height'] = $imgwh['height'];
                    } else {
                        $rtn['error'] = ['code' => 422, 'message' => $uploader->get_last_error()];
                    }
                } else {
                    $rtn['error'] = ['code' => 422, 'message' => '未配置文件上传器'];
                }
                @unlink($filePath);
            } else {
                @unlink("{$filePath}.part");
                $rtn['error'] = ['code' => 422, 'message' => '无法保存文件'];
            }

            return $rtn;
        }
        $rtn['error'] = ['code' => 102, 'message' => '数据不完整'];

        return $rtn;
    }

    /**
     * 是否可以上传.
     *
     * @param string $ext 文件扩展名
     *
     * @return bool
     */
    protected function allowed($ext) {
        $allowed = 'jpg,gif,png,bmp,jpeg,zip,rar,7z,tar,gz,bz2,doc,docx,txt,ppt,pptx,xls,xlsx,pdf,mp3,avi,mp4,flv,swf,apk';
        $allowed = explode(',', $allowed);

        return in_array(ltrim($ext, '.'), $allowed);
    }

    /**
     * 水印图片
     *
     * @return string|null
     */
    protected function watermark() {
        return null;
    }
}