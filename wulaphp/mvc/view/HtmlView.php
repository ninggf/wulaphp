<?php

namespace wulaphp\mvc\view;

class HtmlView extends View implements IModuleView {

    /**
     * 绘制
     *
     * @return string
     * @throws
     */
    public function render() {
        $tpl = MODULES_PATH . $this->tpl . '.php';
        if (is_file($tpl)) {
            extract($this->data);
            @ob_start();
            @include $tpl;
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