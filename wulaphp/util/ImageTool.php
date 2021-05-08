<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util;

use wulaphp\app\App;

include WULA_ROOT . 'vendors/third/image.class.php';

/**
 * 图片工具.
 *
 * @package wulaphp\util
 */
class ImageTool {
    public static  $MIMES     = [
        'image/bmp'                => 'bmp',
        'image/cis-cod'            => 'cod',
        'image/png'                => 'png',
        'image/gif'                => 'gif',
        'image/ief'                => 'ief',
        'image/jpeg'               => 'jpg',
        'image/pipeg'              => 'jfif',
        'image/svg+xml'            => 'svg',
        'image/tiff'               => 'tiff',
        'image/x-cmu-raster'       => 'ras',
        'image/x-cmx'              => 'cmx',
        'image/x-icon'             => 'ico',
        'image/x-portable-anymap'  => 'pnm',
        'image/x-portable-bitmap'  => 'pbm',
        'image/x-portable-graymap' => 'pgm',
        'image/x-portable-pixmap'  => 'ppm',
        'image/x-rgb'              => 'rgb',
        'image/x-xbitmap'          => 'xbm',
        'image/x-xpixmap'          => 'xpm'
    ];
    private        $file;
    private        $ext;
    private static $POSITIONS = ['tl', 'tm', 'tr', 'ml', 'mm', 'mr', 'bl', 'bm', 'br'];

    public function __construct($file) {
        if (file_exists($file) && self::isImage($file)) {
            $this->file = $file;
            $this->ext  = strtolower(ltrim(strrchr($file, '.'), '.'));
        } else {
            $this->file = false;
        }
    }

    /**
     * 生成缩略图
     *
     * @param array  $size 尺寸集.
     * @param string $sep
     * @param string $replace
     *
     * @return array
     */
    public function thumbnail($size = [[80, 60]], $sep = '-', $replace = '') {
        $files = [];
        if ($this->file && !empty ($size)) {
            foreach ($size as $i => $s) {
                if (is_array($s) && isset ($s [0]) && isset ($s [1])) {
                    $width  = intval($s [0]);
                    $height = intval($s [1]);
                    if (!$sep) {
                        $tfile = $this->file;
                    } else {
                        if (isset($s[2])) {
                            $file  = $s[2];
                            $tfile = get_thumbnail_filename($this->file, $file, 0, $sep);
                        } else {
                            $tfile = get_thumbnail_filename($this->file, $width, $height, $sep);
                        }
                        if ($replace) {
                            $tfile = str_replace($replace, '', $tfile);
                        }
                        if (is_file($tfile)) {
                            $files [ $i ] = $tfile;
                            continue;
                        }
                    }
                    $image = new \image ($this->file);
                    $image->attach(new \image_fx_resize ($width, $height));
                    $rst = $image->save($tfile);
                    if (!$rst) {
                        log_warn('生成缩略图失败:' . $tfile, 'image_tool');
                    } else {
                        $files [ $i ] = $tfile;
                    }
                    $image->destroyImage();
                    if (!$sep) {
                        break;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * 裁剪
     *
     * @param int         $x
     * @param int         $y
     * @param int         $w
     * @param int         $h
     * @param string|null $destFile
     *
     * @return bool|null
     */
    public function crop($x, $y, $w, $h, $destFile = null) {
        $file = $this->file;
        if ($this->file) {
            $image = new \image ($this->file);
            $ow    = $image->imagesx();
            $oh    = $image->imagesy();
            if (empty ($w)) {
                $w = $ow - $x;
            } else if ($w < 0) {
                $w = $ow + $w - $x;
            }
            if ($w <= 0) {
                return $this->file;
            }
            if (empty ($h)) {
                $h = $oh - $y;
            } else if ($h < 0) {
                $h = $oh + $h - $y;
            }
            if ($h <= 0) {
                return $this->file;
            }

            $tx = $x;
            $ty = $y;
            $nw = $w;
            $nh = $h;

            $fx = new \image_fx_crop ($tx, $ty, $nw, $nh);
            $image->attach($fx);
            if (!$destFile) {
                $destFile = $this->file;
            }
            if ($image->save($destFile)) {
                $file = $destFile;
            } else {
                $file = null;
            }
            $image->destroyImage();
        }

        return $file;
    }

    /**
     * 通过打马赛克的方式去水印
     *
     * @param $pos
     * @param $size
     */
    public function mosaic($pos, $size) {
        if ($this->file) {
            $image = new \image ($this->file);
            $image->attach(new \image_fx_mosaic ($pos, $size));
            $image->save($this->file);
            $image->destroyImage();
        }
    }

    /**
     * 压缩优化。
     * 1. png格式图片压缩需要通过`pngquant`，请安装它并在media配置中配置pngquant指向pngquant可执行文件。
     * 2. 无法压缩gif图片.
     *
     * @param int    $quality
     * @param string $file
     *
     * @return bool
     */
    public function optimize($quality = 70, $file = null) {
        if ($this->ext == 'png') {
            $pngquant = App::cfg('upload.pngquant');
            if ($pngquant && is_executable($pngquant)) {
                $fileTmp        = $this->file . '.tmp';
                $mq             = 90;
                $quality        = min(max(intval($quality), 60), 89);
                $cmd            = escapeshellcmd($pngquant);
                $arg            = "-f --skip-if-larger --quality $quality-$mq -o " . escapeshellarg($fileTmp) . ' -- ' . escapeshellarg($this->file);
                $descriptorspec = [
                    0 => ["pipe", "r"],  // 标准输入，子进程从此管道中读取数据
                    1 => ["pipe", "w"],  // 标准输出，子进程向此管道中写入数据
                    2 => ["pipe", "w"] // 标准错误，子进程向此管道中写入数据
                ];
                $process        = @proc_open($cmd . ' ' . $arg, $descriptorspec, $pipes, APPROOT);
                $output         = '';
                $error          = '';
                $rtn            = 1;
                if ($process && is_resource($process)) {
                    @stream_set_blocking($pipes[1], 0);
                    @stream_set_blocking($pipes[2], 0);
                    while (true) {
                        $info = @proc_get_status($process);
                        if (!$info) {
                            break;
                        }

                        if (!$info['running']) {
                            $rtn    = $info['exitcode'];
                            $output = @fgets($pipes[1], 1024);
                            $error  = @fgets($pipes[2], 1024);
                            break;
                        } else {
                            usleep(200);
                        }
                    }

                    foreach ($pipes as $p) {
                        @fclose($p);
                    }
                    @proc_close($process);
                }
                if ($rtn) {
                    log_warn(implode("\n", [$output, $error]), 'png');

                    return false;
                }

                return @rename($fileTmp, $file ? $file : $this->file);
            }
        } else if ($this->ext == 'jpg' || $this->ext == 'jpeg') {
            $img     = imagecreatefromjpeg($this->file);
            $fileTmp = $this->file . '.tmp';

            if (@imagejpeg($img, $fileTmp, min(max(intval($quality), 60), 90))) {
                return @rename($fileTmp, $file ? $file : $this->file);
            }
        }

        return false;
    }

    /**
     * 添加水印
     *
     * @param string $mark    水印图片
     * @param string $pos     位置
     * @param string $minSize 最小值
     *
     * @return bool
     */
    public function watermark(string $mark, string $pos, string $minSize = '', string $transxy = '') {
        if ($this->file && is_file($mark)) {
            $image     = new \image ($this->file);
            $iw        = intval($image->imagesx());
            $ih        = intval($image->imagesy());
            $watermark = new \image ($mark);
            $w         = 3 * intval($watermark->imagesx());
            $h         = 3 * intval($watermark->imagesy());
            if ($minSize) {
                $minSize = explode('x', $minSize);
                $w       = intval($minSize [0]);
                if (isset ($minSize [1])) {
                    $h = intval($minSize [1]);
                } else {
                    $h = $w;
                }
            }
            if ($iw > $w && $ih > $h) {
                if ($pos == 'rd') {
                    $pos = array_rand(self::$POSITIONS);
                    $pos = self::$POSITIONS [ $pos ];
                    if ($pos == 'mm') {
                        $pos = 'br';
                    }
                }
                $trans = $transxy ?: App::cfg('upload.transxy');
                if (!preg_match('/^[1-9]\d*x[1-9]\d*$/', $trans)) {
                    $trans = '0x0';
                }
                $image->attach(new \image_draw_watermark ($watermark, $pos, $trans));
                $rst = $image->save($this->file);
                if (!$rst) {
                    log_error('添加水印失败:' . $this->file);

                    return false;
                }
            }
            $image->destroyImage();
        }

        return true;
    }

    /**
     * delete thumbnail
     *
     * @param string $filename
     *
     * @return boolean
     */
    public static function deleteThumbnail($filename) {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            return false;
        }
        $shortname = substr($filename, 0, $pos);
        $ext       = substr($filename, $pos);
        $filep     = $shortname . '-*' . $ext;
        $files     = glob($filep);
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }

        return true;
    }

    /**
     * 是不是图片.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function isImage($file) {
        $ext = strrchr($file, '.');

        return in_array(strtolower($ext), ['.jpeg', '.jpg', '.gif', '.png']);
    }

    /**
     * 下载远程图片到本地.
     *
     * @param string|array          $imgUrls   要下载的图片地址数组或地址.
     * @param \wulaphp\io\IUploader $uploader  图片上传器.
     * @param int                   $timeout   超时时间.
     * @param array                 $watermark 水印设置.
     * @param array                 $resize    重置大小.
     * @param string                $referer   引用.
     *
     * @return array (url,name,path,ext)
     */
    public static function download($imgUrls, $uploader, $timeout = 30, $watermark = [], $resize = [], $referer = '') {
        // 忽略抓取时间限制
        set_time_limit(300);
        $tmpNames = [];
        $savePath = TMP_PATH . 'rimgs' . DS;
        if (!file_exists($savePath) && !mkdir($savePath, 0755, true)) {
            return [];
        }
        if (is_string($imgUrls)) {
            $imgUrls = [$imgUrls];
        }
        $callback = new ImageDownloadCallback ($savePath, $uploader, $watermark, $resize);
        $clients  = [];
        foreach ($imgUrls as $imgUrl) {
            if (is_array($imgUrl)) {
                [$imgUrl, $mosaic] = $imgUrl;
            } else {
                $mosaic = null;
            }
            if (strpos($imgUrl, "http") !== 0) {
                continue;
            }
            $client = CurlClient::getClient($timeout);
            if (!$referer) {
                $referer = $imgUrl;
            }
            $client                        = $client->prepareGet($imgUrl, $callback, $referer);
            $callback->mosaics [ $imgUrl ] = $mosaic;
            if ($client) {
                $clients [ $imgUrl ] = $client;
            }
        }
        if ($clients) {
            $rsts = CurlClient::execute($clients);
            if ($rsts [0]) {
                foreach ($rsts [0] as $url => $rst) {
                    if ($rst) {
                        $tmpNames [ $url ] = $rst;
                    }
                }
            }
        }

        return $tmpNames;
    }
}

class ImageDownloadCallback implements CurlMultiExeCallback {
    private $savePath;
    /**
     * @var \wulaphp\io\IUploader
     */
    private $uploader;
    private $watermark;
    private $config;
    private $resize;
    public  $mosaics = [];

    /**
     * ImageDownloadCallback constructor.
     *
     * @param string                $savedPath
     * @param \wulaphp\io\IUploader $uploader
     * @param array                 $watermark
     * @param array                 $resize
     */
    public function __construct($savedPath, $uploader, $watermark, $resize) {
        $this->savePath  = $savedPath;
        $this->uploader  = $uploader;
        $this->watermark = $watermark;
        $this->resize    = $resize;
        $this->config    = [
            "fileType" => [".gif", ".png", ".jpg", ".jpeg", ".bmp"],
            "fileSize" => 5000
        ]; // 文件大小限制，单位KB
    }

    public function onStart($index, $curl, $cdata) {
        return true;
    }

    public function onError($imgUrl, $curl, $cdata) {
        log_error("cannot download img:" . $imgUrl . ' [' . $cdata . ']');

        return null;
    }

    public function onFinish($imgUrl, $data, $curl, $cdata) {
        // 获取请求头
        $contentType = strtolower(curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        if (!strstr($contentType, 'image')) {
            return null;
        }
        $maxSize = 1024 * $this->config ['fileSize'];
        // 格式验证(扩展名验证和Content-Type验证)
        $oriPath  = explode("/", $imgUrl);
        $fileType = strtolower(strrchr($oriPath [ count($oriPath) - 1 ], '.'));
        if (empty ($fileType)) {
            $fileType = '.' . ImageTool::$MIMES [ $contentType ];
        }
        if (!in_array($fileType, $this->config ['fileType'])) {
            return null;
        }
        //生成文件名，相同文件不重复下载
        $tmpName = $this->savePath . md5($imgUrl) . $fileType;
        $size    = @file_put_contents($tmpName, $data);
        if ($size > $maxSize) {
            @unlink($tmpName);

            return null;
        }
        if ($size !== false && $size > 0) {
            if (isset ($this->mosaics [ $imgUrl ]) && $this->mosaics [ $imgUrl ]) {
                [$pos, $size] = $this->mosaics [ $imgUrl ];
                $img = new ImageTool ($tmpName);
                $img->mosaic($pos, $size);
            }
            if ($this->resize) {
                $img = new ImageTool ($tmpName);
                $cnt = count($this->resize);
                if ($cnt == 2) {
                    $img->thumbnail([$this->resize], null);
                } else if ($cnt == 4) {
                    $img->crop($this->resize [0], $this->resize [1], $this->resize [2], $this->resize [3]);
                }
            }
            if ($this->watermark) {
                [$wimg, $pos, $size] = $this->watermark;
                $img = new ImageTool ($tmpName);
                $img->watermark($wimg, $pos, $size);
            }
            $rst = $this->uploader->save($tmpName);
            if ($rst) {
                $rst ['type'] = $fileType;

                return $rst;
            } else {
                log_info($this->uploader->get_last_error(), 'remote_down_pic');
            }
        }

        return null;
    }
}