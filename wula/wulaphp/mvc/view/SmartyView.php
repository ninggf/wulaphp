<?php
namespace wulaphp\mvc\view;

use wulaphp\app\App;
use wulaphp\hooks\view\SmartyHookTrigger;

/**
 * Smarty视图
 *
 * 通过Smarty模板引擎绘制视图。
 *
 * @package view
 */
class SmartyView extends View {

    /**
     *
     * @var Smarty Smarty
     */
    private $__smarty;

    public function __construct($data = array(), $tpl = '', $headers = array('Content-Type'=>'text/html')) {
        if (! isset ( $headers ['Content-Type'] )) {
            $headers ['Content-Type'] = 'text/html';
        }
        parent::__construct ( $data, $tpl, $headers );
    }

    /**
     * 绘制
     */
    public function render() {
<<<<<<< HEAD
=======
        if ($this->relatedPath) {
            $this->tpl = $this->relatedPath . $this->tpl;
        }
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
        $tpl = MODULES_PATH . $this->tpl;
        $devMod = App::bcfg ( 'develop_mode' );
        if (is_file ( $tpl )) {
            $this->__smarty = new \Smarty ();
            $this->__smarty->template_dir = MODULES_PATH; // 模板目录
            $tpl = str_replace ( DS, '/', $this->tpl );
            $tpl = explode ( '/', $tpl );
            array_pop ( $tpl );
            $sub = implode ( DS, $tpl );
            $this->__smarty->compile_dir = TMP_PATH . '#tpls_c' . DS . $sub; // 模板编译目录
            $this->__smarty->cache_dir = TMP_PATH . '#tpls_cache' . DS . $sub; // 模板缓存目录
            $trigger = new SmartyHookTrigger ();
            $trigger->init_smarty_engine ( $this->__smarty );
            $trigger->init_view_smarty_engine ( $this->__smarty );
            $this->__smarty->compile_check = true;
            $this->__smarty->_dir_perms = 0755;
            if ($devMod) {
                $this->__smarty->compile_check = true;
            } else {
                $this->__smarty->compile_check = false;
            }
            if ($devMod) {
                $this->__smarty->caching = false;
            }
            $this->__smarty->error_reporting = KS_ERROR_REPORT_LEVEL;
        } else {
            if ($devMod) {
                die ( 'The view template ' . $tpl . ' is not found' );
            }
            trigger_error ( 'The view template ' . $tpl . ' is not found', E_USER_ERROR );
        }
        
        $this->__smarty->assign ( $this->data ); // 变量
        $this->__smarty->assign ( '_css_files', $this->sytles );
        $this->__smarty->assign ( '_js_files', $this->scripts );
        $this->__smarty->assign ( '_current_template_file', $this->tpl );
        return $this->__smarty->fetch ( $this->tpl );
    }
}