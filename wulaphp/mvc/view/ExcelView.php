<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\view;

use PhpOffice\PhpSpreadsheet\IOFactory;
use wulaphp\io\Response;

class ExcelView extends View implements IModuleView {
    private   $fileName;
    private   $saveFileName;
    protected $allowedStyles = [
        'font'         => 'getFont',
        'align'        => 'getAlignment',
        'numberFormat' => 'getNumberFormat',
        'fill'         => 'getFill',
        'borders'      => 'getBorders'
    ];

    /**
     * ExcelView constructor.
     *
     * @param string $filename 默认下载保存的文件名
     * @param array  $data     数据
     * @param string $tpl      模板
     */
    public function __construct($filename, $data, $tpl = '') {
        $this->fileName = $filename;
        parent::__construct($data, $tpl);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function render() {
        if (defined('LANGUAGE')) {
            $tpl = MODULES_PATH . $this->tpl . '_' . LANGUAGE . '.xlsx';
            if (is_file($tpl)) {
                $this->tpl .= '_' . LANGUAGE;
            } else if (($pos = strpos(LANGUAGE, '-', 1))) {
                $lang = substr(LANGUAGE, 0, $pos);
                $tpl  = MODULES_PATH . $this->tpl . '_' . $lang . '.xlsx';
                if (is_file($tpl)) {
                    $this->tpl .= '_' . $lang;
                }
            }
        }
        $tpl = MODULES_PATH . $this->tpl . '.xlsx';
        if (is_file($tpl)) {
            try {
                $reader      = IOFactory::createReader('Xlsx');
                $spreadsheet = $reader->load($tpl);
                $worksheet   = $spreadsheet->getActiveSheet();
                foreach ($this->data as $row => $cols) {//遍历所有行
                    foreach ($cols as $col => $val) {//遍历所有列
                        if (is_array($val)) {
                            list($val, $style) = $val;
                        } else {
                            $style = null;
                        }
                        $cell = "$col$row";
                        $worksheet->setCellValue($cell, $val);
                        if ($style && is_array($style)) {
                            $ostyle = $worksheet->getStyle($cell);
                            foreach ($style as $s => $styles) {//遍历所有样式
                                if (isset($this->allowedStyles[ $s ])) {
                                    $func   = $this->allowedStyles[ $s ];
                                    $cstyle = $ostyle->{$func}();//getFont,getFill,getBorders,getAlignment
                                    foreach ($styles as $key => $vs) {//遍历所有值
                                        if (is_array($vs)) {
                                            $func1 = 'get' . ucfirst($key);
                                            if (!method_exists($cstyle, $func1)) {
                                                continue;
                                            }
                                            $cstyle = $cstyle->{$func1}();
                                            foreach ($vs as $k => $v) {//遍历子样式
                                                $func2 = 'set' . ucfirst($k);
                                                if (method_exists($cstyle, $func1)) {
                                                    $cstyle->{$func2}($v);
                                                }
                                            }
                                        } else {
                                            $func1 = 'set' . ucfirst($key);
                                            if (method_exists($cstyle, $func1)) {
                                                $cstyle->{$func1}($vs);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                if ($this->saveFileName) {
                    $writer->save($this->saveFileName);

                    return true;
                } else {
                    $writer->save('php://output');
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw_exception('tpl is not found:' . $this->tpl . '.xlsx');
        }

        return null;
    }

    /**
     * @param $file
     *
     * @return false|string
     * @throws \Exception
     */
    public function save($file) {
        $this->saveFileName = $file;

        return $this->render();
    }

    protected function setHeader() {
        Response::nocache();
        $this->headers['Content-Type']        = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $this->fileName . '.xlsx"';
    }
}