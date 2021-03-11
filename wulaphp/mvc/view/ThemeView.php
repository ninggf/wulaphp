<?php

namespace wulaphp\mvc\view;

use wulaphp\app\App;

/**
 * 模板视图.
 *
 * @author Guangfeng
 *
 */
class ThemeView extends View {

    /**
     *
     * @var \Smarty
     */
    private $__smarty;
    private $__mustache = false;

    /**
     * ThemeView constructor.
     *
     * @param array  $data
     * @param string $tpl
     * @param array  $headers
     *
     * @filter init_smarty_engine $smarty
     * @filter init_template_smarty_engine $smarty
     * @throws \Exception
     */
    public function __construct($data = [], $tpl = '', $headers = ['Content-Type' => 'text/html;charset=UTF-8']) {
        if (!isset ($headers ['Content-Type'])) {
            $headers ['Content-Type'] = 'text/html;charset=UTF-8';
        }
        parent::__construct($data, $tpl, $headers);
    }

    /**
     * 绘制.
     * @throws \Exception
     */
    public function render() {
        $tplInfo = pathinfo($this->tpl);
        if (!isset($tplInfo['extension'])) {
            $tplInfo['extension'] = 'tpl';
            $this->tpl            .= '.tpl';
        }
        if (defined('LANGUAGE')) {
            $lang_tpl = $tplInfo['dirname'] . DS . $tplInfo['filename'] . '_' . LANGUAGE . '.' . $tplInfo['extension'];
            $tpl      = THEME_PATH . $lang_tpl;
            if (is_file($tpl)) {
                $this->tpl = $lang_tpl;
            } else if (($pos = strpos(LANGUAGE, '-', 1))) {
                $lang_tpl = $tplInfo['dirname'] . DS . $tplInfo['filename'] . '_' . substr(LANGUAGE, 0, $pos) . '.' . $tplInfo['extension'];
                $tpl      = THEME_PATH . $lang_tpl;
                if (is_file($tpl)) {
                    $this->tpl = $lang_tpl;
                }
            }
        }
        $tpl    = THEME_PATH . $this->tpl;
        $devMod = App::bcfg('smarty.dev', false);
        if (is_file($tpl)) {
            $this->__smarty = new \Smarty ();
            $tpl            = str_replace(DS, '/', $this->tpl);
            $tpl            = explode('/', $tpl);
            $sub            = implode(DS, array_slice($tpl, 0, - 1));

            $this->__smarty->setTemplateDir(THEME_PATH);
            $this->__smarty->setCompileDir(TMP_PATH . 'themes_c' . DS . $sub);
            $this->__smarty->setCacheDir(TMP_PATH . 'themes_cache' . DS . $sub);
            $this->__smarty->setDebugTemplate(SMARTY_DIR . 'debug.tpl');
            fire('init_smarty_engine', $this->__smarty);
            fire('init_template_smarty_engine', $this->__smarty);
            $this->__smarty->compile_check   = $devMod;
            $this->__smarty->caching         = App::bcfg('smarty.cache', false);
            $this->__smarty->error_reporting = defined('KS_ERROR_REPORT_LEVEL') ? KS_ERROR_REPORT_LEVEL : 0;
        } else {
            throw new \Exception(__('The template %s is not found', THEME_DIR . '/' . $this->tpl));
        }
        $this->__smarty->assign($this->data);
        $this->__smarty->assign('_css_files', $this->sytles);
        $this->__smarty->assign('_js_files', $this->scripts);
        $this->__smarty->assign('_current_template_file', $this->tpl);
        @ob_start();
        if ($this->__mustache) {
            $filter  = new MustacheFilter();
            $filters = apply_filter('smarty\getFilters', ['pre' => [[$filter, 'pre']], 'post' => [[$filter, 'post']]]);
        } else {
            $filters = apply_filter('smarty\getFilters', ['pre' => [], 'post' => []]);
        }

        if ($filters) {
            foreach ($filters as $type => $cbs) {
                foreach ($cbs as $cb) {
                    $this->__smarty->registerFilter($type, $cb);
                }
            }
        }
        $this->__smarty->display($this->tpl);
        $content = @ob_get_clean();

        if ($devMod && $content) {
            $debugArg = App::cfg('smarty.debugArg', '');
            if ($debugArg && rqset($debugArg)) {
                $debugArg    = json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $debugString = '<script>console.log(' . $debugArg . ')</script>';
                $content     = str_replace('<!--pageEnd-->', $debugString, $content);
            }
        }

        return $content;
    }

    /**
     * 启用mustache.
     *
     * @return $this
     */
    public function mustache() {
        $this->__mustache = true;

        return $this;
    }
}