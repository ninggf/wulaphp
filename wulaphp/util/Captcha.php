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
/**
 * Class Captcha
 * @package wulaphp\util
 * @property-read string $codes    验证码
 * @property array       $reflect  倒置顺序
 */
class Captcha {
    /**
     * 验证码
     * char: 字符
     * angle: 字符偏移的角度 (-30 <= angle <= 30)
     * color: 字符颜色
     *
     * @var array
     * @access private
     */
    private $code = [];

    /**
     * 字体信息
     * space: 字符间隔 (px)
     * size: 字体大小 (px)
     * left: 第一个字符距离图像最左边的象素 (px)
     * top: 字符距离图像最上边的象素 (px)
     * file: 字体文件的路径
     *
     * @var array
     * @access private
     */
    private $font = [];

    /**
     * 图像信息
     * type: 图像类型
     * mime: MIME 类型
     * width: 图像的宽 (px)
     * height: 图像高 (px)
     * func: 创建图像的方法
     *
     * @var array
     * @access private
     */
    private $image = [];

    /**
     * 干扰信息
     * type: 干扰类型 (False 表示不使用)
     * density: 干扰密度
     *
     * @var array
     * @access private
     */
    private $molestation = [];

    /**
     * 背景色 (RGB)
     * r: 红色 (0 - 255)
     * g: 绿色 (0 - 255)
     * b: 蓝色 (0 - 255)
     *
     * @var array
     * @access private
     */
    private $bg_color = [];

    /**
     * 默认前景色 (RGB)
     * r: 红色 (0 - 255)
     * g: 绿色 (0 - 255)
     * b: 蓝色 (0 - 255)
     *
     * @var array
     * @access private
     */
    private $fg_color = [];

    /**
     * Session 变量名
     *
     * @var string
     * @access private
     */
    private $session = '';
    private $_thecode;
    private $reflect;

    /**
     * 构造函数
     *
     * @param string $sessionName
     */
    public function __construct(string $sessionName = 'auth_code') {
        $this->setCode(null);
        $this->setMolestation(null);
        $this->setBgColor(null);
        $this->setImage(null);
        $this->setFont(null); // code, image 两部分必须在 font 之前定义
        $this->setSession($sessionName);
    }

    /**
     * 析构函数
     *
     * @access public
     */
    public function __destruct() {
        unset ($this->code);
        unset ($this->molestation);
        unset ($this->bg_color);
        unset ($this->fg_color);
        unset ($this->image);
        unset ($this->font);
        unset ($this->session);
    }

    public function __get($name) {
        if ($name == 'codes') {
            return $this->_thecode;
        } else if ($name == 'reflect') {
            return $this->reflect;
        }

        return null;
    }

    /**
     * 设置验证码
     *
     *
     * @param array $code 字符信息
     *                    1. characters string|array 允许的字符
     *                    2. length int 验证码长度
     *                    3. deflect boolean 字符是否偏转
     *                    4. multicolor boolean 字符是否彩色
     *
     * @return \wulaphp\util\Captcha
     */
    public function setCode(array $code) {
        $this->code = [
            'characters' => 'A-H,K-N,P-R,U-Y,2-4,6-9',
            'length'     => 4,
            'deflect'    => true,
            'multicolor' => false
        ];
        if (is_array($code)) {
            if (isset ($code ['characters'])) {
                $this->code ['characters'] = $code['characters'];
            }
            if (isset ($code ['chars'])) {
                $this->code ['characters'] = $code['chars'];
            }
            if (is_numeric($code ['length']) && $code ['length'] > 0) {
                $this->code['length'] = $code['length'];
            }
            if (is_bool($code ['deflect'])) {
                $this->code ['deflect'] = $code['deflect'];
            }
            if (is_bool($code ['reflect'])) {
                $this->code ['reflect'] = $code['reflect'];
                $this->code ['deflect'] = false;
            }
            if (is_bool($code ['multicolor'])) {
                $this->code ['multicolor'] = $code['multicolor'];
            }
        }

        return $this;
    }

    /**
     * 设置 session 变量名
     *
     * @param string session 变量名
     *
     * @return \wulaphp\util\Captcha
     */
    public function setSession(string $session) {
        if (!empty ($session)) {
            $this->session = $session;
        } else {
            $this->session = 'auth_code';
        }

        return $this;
    }

    /**
     * 设置背景色
     *
     * @param array $color RGB 颜色
     *
     * @return \wulaphp\util\Captcha
     */
    public function setBgColor(array $color) {
        if ($this->isColor($color)) {
            $this->bg_color = $color;
        } else {
            $this->bg_color = ['r' => 255, 'g' => 255, 'b' => 255];
        }

        // 设置默认的前景色, 与背景色相反
        $fg_color = [
            'r' => 255 - $this->bg_color ['r'],
            'g' => 255 - $this->bg_color ['g'],
            'b' => 255 - $this->bg_color ['b']
        ];

        return $this->setFgColor($fg_color);
    }

    /**
     * 设置干扰信息
     *
     * @param array $molestation 干扰信息
     *                           type string 干扰类型 (选项: false, 'point', 'line')
     *                           density string 干扰密度 (选项: 'normal', 'muchness', 'fewness')
     *
     * @return \wulaphp\util\Captcha
     */
    public function setMolestation(array $molestation) {
        $this->molestation = ['type' => 'point', 'density' => 'normal'];
        if (is_array($molestation)) {
            if (isset ($molestation ['type']) && in_array($molestation['type'], ['line', 'point', 'both'])) {
                $this->molestation ['type'] = $molestation['type'];
            } else {
                $this->molestation['type'] = false;
            }
            if (is_string($molestation ['density'])) {
                $this->molestation ['density'] = $molestation['density'];
            }
        }

        return $this;
    }

    /**
     * 设置字体信息
     *
     * @param array $font 字体信息
     *                    size int 字体大小 (px)
     *                    file string 字体文件的路径
     *
     * @return \wulaphp\util\Captcha
     */
    public function setFont(array $font) {
        $this->font = [
            'size' => 12,
            'file' => __DIR__ . '/fonts/arial.ttf',
            'left' => 0,
        ];

        $this->calcOffset();
        if (is_array($font)) {
            if (is_numeric($font ['size']) && $font ['size'] > 0) {
                $this->font ['size'] = $font ['size'];
                $this->calcOffset();
            }
            if ($font['file'] && file_exists($font ['file'])) {
                $this->font ['file'] = $font['file'];
            }
        }

        return $this;
    }

    /**
     * 设置图像信息
     *
     * @param array $image 图像信息
     *                     type string 图像类型 (选项: 'png', 'wbmp', 'jpg')
     *                     width int 图像宽 (px)
     *                     height int 图像高 (px)
     *
     * @return \wulaphp\util\Captcha
     */
    public function setImage(array $image) {
        $information = $this->getImageType('png');
        $this->image = [
            'type'   => 'png',
            'mime'   => $information ['mime'],
            'func'   => $information ['func'],
            'width'  => 70,
            'height' => 25,
            'alpha'  => false
        ];
        if (is_array($image)) {
            if (is_numeric($image ['width']) && $image ['width'] > 0) {
                $this->image ['width'] = $image ['width'];
            }
            if (is_numeric($image ['height']) && $image ['height'] > 0) {
                $this->image ['height'] = $image ['height'];
            }
            if (isset($image['alpha'])) {
                $this->image['alpha'] = $image['alpha'];
            } else {
                $this->image['alpha'] = false;
            }
            $information = $this->getImageType($image ['type']);
            if (is_array($information)) {
                $this->image['type']  = $image['type'];
                $this->image ['mime'] = $information ['mime'];
                $this->image ['func'] = $information ['func'];
            }
        }

        return $this;
    }

    /**
     * 绘制图像
     *
     *
     * @param string $filename 文件名, 留空表示输出到浏览器
     *
     * @return string|null
     */
    public function paint(string $filename = '') {
        $colors = [];
        // 创建图像
        $im = imagecreatetruecolor($this->image ['width'], $this->image ['height']);
        // 设置图像背景
        if ($this->image['alpha'] === false) {
            $bg_color = imagecolorallocate($im, $this->bg_color ['r'], $this->bg_color ['g'], $this->bg_color ['b']);
        } else {
            $bg_color = imagecolorallocatealpha($im, $this->bg_color ['r'], $this->bg_color ['g'], $this->bg_color ['b'], 127);
            imagecolortransparent($im, $bg_color);
        }
        imagefill($im, 0, 0, $bg_color);
        // 生成验证码相关信息
        $code    = $this->generateCode();
        $reflect = [];
        // 生成的验证码
        $the_code = '';
        // 向图像中写入字符
        $num         = count($code);
        $current_top = $this->font ['top'];
        for ($i = 0; $i < $num; $i++) {
            $current_left = $i * $this->font['interval'] + $this->font['offset'];
            $colorId      = implode('-', $code[ $i ]['color']);
            if (!isset($colors[ $colorId ])) {
                $font_color         = imagecolorallocate($im, $code [ $i ] ['color'] ['r'], $code [ $i ] ['color'] ['g'], $code [ $i ] ['color'] ['b']);
                $colors[ $colorId ] = $font_color;
            } else {
                $font_color = $colors[ $colorId ];
            }
            //imagerectangle($im, $current_left, $current_top - $this->font['size'], $current_left + $this->font['size'], $current_top, $font_color);
            if ($code[ $i ]['angle'] == 180) {
                $reflect[] = 1;
                $top       = $current_top - $this->font['size'] + 5;
                $left      = $current_left + $this->font['size'] + 5;
                imagettftext($im, $this->font ['size'], 180, $left, $top, $font_color, $this->font ['file'], $code [ $i ] ['char']);
            } else {
                $reflect[] = 0;
                imagettftext($im, $this->font ['size'], $code [ $i ] ['angle'], $current_left, $current_top, $font_color, $this->font ['file'], $code [ $i ] ['char']);
            }
            $the_code .= $code [ $i ] ['char'];
        }
        $this->_thecode = '';
        $this->reflect  = $reflect;
        // 用 md5() 给密码加密, 写入 session
        $_SESSION [ $this->session ]                = md5($the_code);
        $_SESSION [ $this->session . '_none_case' ] = md5(strtolower($the_code));
        // 绘制图像干扰
        $this->paintMolestation($im);
        // 转8位
        imagetruecolortopalette($im, false, 255);
        // 保留背影透明
        imagesavealpha($im, true);
        // 输出
        if ($filename) {
            if ($filename == 'data:base64') {
                ob_start();
                $this->image['func'] ($im, null, 9);
                imagedestroy($im);
                $string = ob_get_clean();
                if ($string) {
                    return 'data:' . $this->image['mime'] . ';base64,' . base64_encode($string);
                }

                return false;
            } else {
                $fname = $filename . '.' . $this->image ['type'];
                $this->image['func'] ($im, $fname, 9);
                imagedestroy($im);

                return $fname;
            }
        } else {
            header("Cache-Control: no-cache, must-revalidate");
            header("Content-type: " . $this->image ['mime']);
            $this->image['func'] ($im, null, 9);
        }
        imagedestroy($im);

        return null;
    }

    /**
     * 验证用户输入的验证码
     *
     * @param string $input         用户输入的字符串
     * @param bool   $is_match_case 是否区分大小写
     * @param bool   $remove        验证成功时从session中删除验证码
     *
     * @return boolean 正确返回 true
     */
    public function validate(string $input, bool $is_match_case = true, bool $remove = true) {
        if ($is_match_case) {
            $rst = strcmp($_SESSION [ $this->session ], md5($input)) == 0;
        } else {
            $rst = strcmp($_SESSION [ $this->session . '_none_case' ], md5(strtolower($input))) == 0;
        }
        if ($rst && $remove) {
            sess_del($this->session);
            sess_del($this->session . '_none_case');
        }

        return $rst;
    }

    /**
     * 设置前景色
     *
     * @param array $color RGB 颜色
     *
     * @return \wulaphp\util\Captcha
     */
    private function setFgColor(array $color) {
        if ($this->isColor($color)) {
            $this->fg_color = $color;
        } else {
            $this->fg_color = ['r' => 0, 'g' => 0, 'b' => 0];
        }

        return $this;
    }

    /**
     * 生成随机验证码
     *
     * @access private
     * @return array 生成的验证码
     */
    private function generateCode() {
        // 创建允许的字符串
        $array_allow = [];
        if (is_array($this->code['characters'])) {
            $array_allow = $this->code['characters'];
        } else {
            $characters = explode(',', $this->code ['characters']);
            $num        = count($characters);
            for ($i = 0; $i < $num; $i++) {
                if (substr_count($characters [ $i ], '-') > 0) {
                    $character_range = explode('-', $characters [ $i ]);
                    for ($j = ord($character_range [0]); $j <= ord($character_range [1]); $j++) {
                        $array_allow [] = chr($j);
                    }
                } else {
                    $array_allow [] = $characters[ $i ];
                }
            }
        }

        // 生成随机字符串
        mt_srand(( double )microtime() * 1000000);
        $code = [];
        $i    = 0;
        while ($i < $this->code ['length']) {
            $index                = mt_rand(0, count($array_allow) - 1);
            $code [ $i ] ['char'] = $array_allow [ $index ];
            if ($this->code ['deflect']) {
                $code [ $i ] ['angle'] = mt_rand(-45, 45);
            } else {
                $code [ $i ] ['angle'] = 0;
            }
            if ($this->code['reflect'] && rand(0, 99) > 50) {
                $code [ $i ] ['angle'] = 180;
            }
            if ($this->code ['multicolor']) {
                $code [ $i ] ['color'] ['r'] = mt_rand(0, 255);
                $code [ $i ] ['color'] ['g'] = mt_rand(0, 255);
                $code [ $i ] ['color'] ['b'] = mt_rand(0, 255);
            } else {
                $code [ $i ] ['color'] ['r'] = $this->fg_color ['r'];
                $code [ $i ] ['color'] ['g'] = $this->fg_color ['g'];
                $code [ $i ] ['color'] ['b'] = $this->fg_color ['b'];
            }
            $i++;
        }

        return $code;
    }

    /**
     * 获取图像类型
     *
     * @param string $extension 扩展名
     *
     * @return mixed 错误时返回 false
     */
    private function getImageType(string $extension) {
        switch (strtolower($extension)) {
            case 'png' :
                $information ['mime'] = image_type_to_mime_type(IMAGETYPE_PNG);
                $information ['func'] = 'imagepng';
                break;
            case 'wbmp' :
                $information ['mime'] = image_type_to_mime_type(IMAGETYPE_WBMP);
                $information ['func'] = 'imagewbmp';
                break;
            case 'jpg' :
            case 'jpeg' :
            case 'jpe' :
                $information ['mime'] = image_type_to_mime_type(IMAGETYPE_JPEG);
                $information ['func'] = 'imagejpeg';
                break;
            default :
                $information = false;
        }

        return $information;
    }

    /**
     * 绘制图像干扰
     *
     * @param resource $im 图像资源
     *
     * @return void
     */
    private function paintMolestation($im) {
        // 总象素
        $num_of_pels = ceil($this->image ['width'] * $this->image ['height'] / 5);
        switch ($this->molestation ['density']) {
            case 'fewness' :
                $density = $num_of_pels / 3;
                break;
            case 'muchness' :
                $density = $num_of_pels / 3 * 2;
                break;
            case 'normal' :
            default :
                $density = $num_of_pels / 2;
        }

        switch ($this->molestation ['type']) {
            case 'point' :
                $this->paintPoints($im, $density);
                break;
            case 'line' :
                $density = $density / 20;
                $this->paintLines($im, $density);
                break;
            case 'both' :
                $density = $density / 2;
                $this->paintPoints($im, $density);
                $density = $density / 20;
                $this->paintLines($im, $density);
                break;
            default :
                break;
        }
    }

    /**
     * 画点
     *
     * @param resource $im       图像资源
     * @param int      $quantity 点的数量
     *
     * @return void
     */
    private function paintPoints($im, $quantity) {
        mt_srand(( double )microtime() * 1000000);

        for ($i = 0; $i <= $quantity; $i++) {
            $randcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $this->image ['width']), mt_rand(0, $this->image ['height']), $randcolor);
        }
    }

    /**
     * 画线
     *
     * @param resource $im       图像资源
     * @param int      $quantity 线的数量
     *
     * @return void
     */
    private function paintLines($im, $quantity) {
        mt_srand(( double )microtime() * 1000000);

        for ($i = 0; $i <= $quantity; $i++) {
            $randcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            $x1        = mt_rand(0, $this->image ['width']);
            $y1        = mt_rand(0, $this->image ['height']);
            $x2        = $x1 + mt_rand(-30, 30);
            $y2        = $y1 + mt_rand(-30, 30);
            imageline($im, $x1, $y1, $x2, $y2, $randcolor);
        }
    }

    /**
     * 检测是否是合法的颜色定义.
     *
     * @param array $color 颜色定义.
     *
     * @return bool
     */
    private function isColor($color) {
        return is_array($color) && is_numeric($color ['r']) && is_numeric($color ['g']) && is_numeric($color ['b']) && ($color ['r'] >= 0 && $color ['r'] <= 255) && ($color ['g'] >= 0 && $color ['g'] <= 255) && ($color ['b'] >= 0 && $color ['b'] <= 255);
    }

    /**
     * 计算位置.
     */
    private function calcOffset() {
        $interval                = $this->image['width'] / $this->code['length'];
        $offset                  = ($interval - $this->font['size']) / 2;
        $this->font ['interval'] = $interval;
        $this->font ['offset']   = $offset;
        $this->font ['top']      = ($this->image ['height'] - $this->font ['size']) / 2 + $this->font ['size'];
    }
}