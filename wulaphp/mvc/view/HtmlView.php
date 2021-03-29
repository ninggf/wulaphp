<?php

namespace wulaphp\mvc\view;
/**
 * php 模板视图.
 *
 * @package wulaphp\mvc\view
 */
class HtmlView extends View implements IModuleView {

    /**
     * 绘制
     *
     * @return string
     * @throws
     */
    public function render() {
        $ext = strrchr($this->tpl, '.');
        if ($ext) {
            $this->tpl = substr($this->tpl, 0, - strlen($ext));
        } else {
            $ext = '.php';
        }
        if (defined('LANGUAGE')) {
            $tpl = MODULES_PATH . $this->tpl . '_' . LANGUAGE . $ext;
            if (is_file($tpl)) {
                $this->tpl .= '_' . LANGUAGE;
            } else if (($pos = strpos(LANGUAGE, '-', 1))) {
                $lang = substr(LANGUAGE, 0, $pos);
                $tpl  = MODULES_PATH . $this->tpl . '_' . $lang . $ext;
                if (is_file($tpl)) {
                    $this->tpl .= '_' . $lang;
                }
            }
        }
        $tpl = MODULES_PATH . $this->tpl . $ext;
        if (is_file($tpl)) {
            extract($this->data);
            @ob_start();
            include $tpl;
            $content = @ob_get_contents();
            @ob_end_clean();

            return $content;
        } else {
            throw_exception('tpl is not found:' . $this->tpl . $ext);
        }

        return '';
    }

    public function setHeader() {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
    }
}