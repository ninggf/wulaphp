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
        if (defined('LANGUAGE')) {
            $tpl = MODULES_PATH . $this->tpl . '_' . LANGUAGE . '.php';
            if (is_file($tpl)) {
                $this->tpl .= '_' . LANGUAGE;
            } else if (($pos = strpos(LANGUAGE, '-', 1))) {
                $lang = substr(LANGUAGE, 0, $pos);
                $tpl  = MODULES_PATH . $this->tpl . '_' . $lang . '.php';
                if (is_file($tpl)) {
                    $this->tpl .= '_' . $lang;
                }
            }
        }
        $tpl = MODULES_PATH . $this->tpl . '.php';
        if (is_file($tpl)) {
            extract($this->data);
            @ob_start();
            include $tpl;
            $content = @ob_get_contents();
            @ob_end_clean();

            return $content;
        } else {
            throw_exception('tpl is not found:' . $this->tpl . '.php');
        }

        return '';
    }

    public function setHeader() {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
    }
}