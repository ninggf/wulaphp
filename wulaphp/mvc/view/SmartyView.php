<?php

namespace wulaphp\mvc\view;

use wulaphp\app\App;

/**
 * Smarty视图
 *
 * 通过Smarty模板引擎绘制视图。
 *
 * @package view
 */
class SmartyView extends View implements IModuleView {

    /**
     *
     * @var \Smarty
     */
    private $__smarty;
    private $__mustache = false;

    /**
     * SmartyView constructor.
     *
     * @param array  $data
     * @param string $tpl
     * @param array  $headers
     *
     * @throws \Exception
     */
    public function __construct($data = [], $tpl = '', $headers = ['Content-Type' => 'text/html;charset=UTF-8']) {
        if (!isset ($headers ['Content-Type'])) {
            $headers ['Content-Type'] = 'text/html;charset=UTF-8';
        }
        parent::__construct($data, $tpl, $headers);
    }

    /**
     * 绘制
     * @filter init_smarty_engine $smarty
     * @filter init_view_smarty_engine $smarty
     * @return string
     *
     * @throws \Exception
     */
    public function render() {
        if (defined('LANGUAGE')) {
            $tpl = MODULES_PATH . $this->tpl . '_' . LANGUAGE . '.tpl';
            if (is_file($tpl)) {
                $this->tpl .= '_' . LANGUAGE;
            } else if (($pos = strpos(LANGUAGE, '-', 1))) {
                $lang = substr(LANGUAGE, 0, $pos);
                $tpl  = MODULES_PATH . $this->tpl . '_' . $lang . '.tpl';
                if (is_file($tpl)) {
                    $this->tpl .= '_' . $lang;
                }
            }
        }
        $tpl    = MODULES_PATH . $this->tpl . '.tpl';
        $devMod = !App::bcfg('smarty.cache',false);
        if (is_file($tpl)) {
            $this->__smarty = new \Smarty ();
            $tpl            = str_replace(DS, '/', $this->tpl);
            $tpl            = explode('/', $tpl);
            $sub            = implode(DS, array_slice($tpl, 0, -1));

            $this->__smarty->setTemplateDir(MODULES_PATH);
            $this->__smarty->setCompileDir(TMP_PATH . 'tpls_c' . DS . $sub);
            $this->__smarty->setCacheDir(TMP_PATH . 'tpls_cache' . DS . $sub);
            $this->__smarty->setDebugTemplate(SMARTY_DIR . 'debug.tpl');

            fire('init_smarty_engine', $this->__smarty);
            fire('init_view_smarty_engine', $this->__smarty);
            $this->__smarty->compile_check = 1;
            if ($devMod) {
                $this->__smarty->caching = false;
            }
            $this->__smarty->error_reporting = defined('KS_ERROR_REPORT_LEVEL') ? KS_ERROR_REPORT_LEVEL : 0;
        } else {
            throw new \Exception(__('The template %s is not found', MODULE_DIR . '/' . $this->tpl . '.tpl'));
        }

        $this->__smarty->assign($this->data); // 变量
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

        $this->__smarty->display($this->tpl . '.tpl');
        $content = @ob_get_clean();

        if ($devMod && $content) {
            $debugArg = App::cfg('smarty.debugArg', '');
            if ($debugArg) {
                $debugArg    = json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $debugString = '<script>console.log([\'pageData\',' . $debugArg . '])</script>';
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