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

class ExcelView extends View implements IModuleView {
    private $fileName;

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
            $content = '';

            return $content;
        } else {
            throw_exception('tpl is not found:' . $this->tpl . '.xlsx');
        }

        return '';
    }

    protected function setHeader() {
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $this->fileName . '.xlsx"';
    }
}