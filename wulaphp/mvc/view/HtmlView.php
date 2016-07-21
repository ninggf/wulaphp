<?php
namespace wulaphp\mvc\view;

class HtmlView extends View {

    /**
     * 绘制
     *
     * @return string
     */
    public function render() {
        $tpl = '';
        if (is_file ( $tpl )) {
            extract ( $this->data );
            @ob_start ();
            include $tpl;
            $content = @ob_get_contents ();
            @ob_end_clean ();
            return $content;
        } else {
            return '';
        }
    }

    public function setHeader() {
        @header ( 'Content-Type: text/html' );
    }
}