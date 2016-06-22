<?php
use wulaphp\mvc\view\ThemeView;
use wulaphp\mvc\view\SmartyView;
<<<<<<< HEAD
=======

function get_condition_value($name, $conditions, $default = '') {
    if (isset ( $conditions [$name] )) {
        return $conditions [$name];
    }
    return $default;
}

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
/**
 * merge arguments.
 *
 * @param array $args the array to be merged
 * @param array $default the array to be merged with
 * @return array the merged arguments array
 */
function merge_args($args, $default) {
    $_args = array ();
    foreach ( $args as $key => $val ) {
        if (is_numeric ( $val ) || is_bool ( $val ) || ! empty ( $val )) {
            $_args [$key] = $val;
        }
    }
    foreach ( $default as $key => $val ) {
        if (! isset ( $_args [$key] )) {
            $_args [$key] = $val;
        }
    }
    return $_args;
}

/**
 * load the template view.
 *
 * @param $tpl
 * @param array $data
 * @param array $headers
 * @global filter:get_custome_tplfile
 * @return ThemeView
 */
function template($tpl, $data = array(), $headers = array('Content-Type'=>'text/html')) {
    $theme = get_theme ();
    $tplname = str_replace ( array (
        '/','.'
    ), '_', basename ( $tpl, '.tpl' ) );
    $_tpl = THEME_DIR . DS . $theme . DS . $tpl;
    $found = false;
    $_tpl = apply_filter ( 'get_custome_tplfile', $_tpl, $data );
    if (is_file ( THEME_PATH . $_tpl )) {
        $tplfile = $_tpl;
    } else {
        $tplfile = THEME_DIR . '/default/' . $tpl;
        $theme = 'default';
    }
    $template_func_file = THEME_PATH . THEME_DIR . DS . $theme . DS . 'template.php';
    if (is_file ( $template_func_file )) {
        include_once $template_func_file;
        $func = 'prepare_template_data';
        if (function_exists ( $func )) {
            $func ( $data );
        }
        $func = 'prepare_' . $tplname . '_template_data';
        if (function_exists ( $func )) {
            $func ( $data );
        }
    }
    $data ['_current_template'] = $tplfile;
    $data ['_current_theme_path'] = THEME_DIR . '/' . $theme;
    $data ['_theme_name'] = $theme;
    $data ['_theme_dir'] = THEME_DIR;
    $data ['_module_dir'] = MODULE_DIR;
    return new ThemeView ( $data, $tplfile, $headers );
}

/**
<<<<<<< HEAD
=======
 * the views in modules.
 *
 * @param string $tpl
 * @param array $data
 * @param array $headers
 * @return SmartyView
 */
function view($tpl, $data = array(), $headers = array('Content-Type'=>'text/html')) {
    return new SmartyView ( $data, $tpl, $headers );
}

/**
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
 * 解析smarty参数.
 *
 * 将参数中 '" 去除比,如 '1' 转换为1.
 *
 * @param array $args 参数数组
 * @return array 解析后的参数
 */
function smarty_parse_args($args) {
    foreach ( $args as $key => $value ) {
        if (strpos ( $value, '_smarty_tpl->tpl_vars' ) !== false) {
            $args [$key] = trim ( $value, '\'"' );
        }
    }
    return $args;
}

/**
 * 将smarty传过来的参数转换为可eval的字符串.
 *
 * @param array $args
 * @return string
 */
function smarty_argstr($args) {
    $a = array ();
    foreach ( $args as $k => $v ) {
        $v1 = trim ( $v );
        if (empty ( $v1 ) && $v1 != '0' && $v1 != 0) {
            continue;
        }
        if ($v == false) {
            $a [] = "'$k'=>false";
        } else {
            $a [] = "'$k'=>$v";
        }
    }
    return 'array(' . implode ( ',', $a ) . ')';
}

/**
 * Smarty here modifier plugin.
 *
 * <code>
 * {'images/logo.png'|here}
 * </code>
 * 以上代表输出模板所在目录下的images/logo.png
 *
 * Type: modifier<br>
 * Name: here<br>
 * Purpose: 输出模板所在目录下资源的URL
 *
 * @staticvar string WEBROOT的LINUX表示.
 * @param array $params 参数
 * @param Smarty $compiler
 * @return string with compiled code
 */
function smarty_modifiercompiler_here($params, $compiler) {
    static $base = null;
    if ($base == null) {
        $base = str_replace ( DS, '/', WWWROOT );
    }
    $tpl = str_replace ( DS, '/', dirname ( $compiler->template->source->filepath ) );
    $tpl = str_replace ( $base, '', $tpl );
    $url = ! empty ( $tpl ) ? trailingslashit ( $tpl ) : '';
    return "safe_url ('{$url}'." . $params [0] . ',true)';
}

function cleanhtml2simple($text) {
    $text = str_ireplace ( array (
        '[page]',' ','　',"\t","\r","\n",'&nbsp;'
    ), '', $text );
    $text = preg_replace ( '#</?[a-z0-9][^>]*?>#msi', '', $text );
    return $text;
}

function smarty_modifiercompiler_clean($params, $compiler) {
    return 'cleanhtml2simple(' . $params [0] . ')';
}

function smarty_modifiercompiler_kk($params, $compiler) {
    $var = $params [0];
    return "'<pre style=\"margin:5px;padding:5px;overflow:auto;\">',var_export($var,true),'</pre>'";
}

/**
 * Smarty static modifier plugin.
 *
 * <code>
 * {resource|static}
 * </code>
 *
 *
 * Type: modifier<br>
 * Name: static<br>
 * Purpose: 取静态资源的URL
 *
 * @param Smarty $compiler
 * @return string with compiled code
 */
function smarty_modifiercompiler_assets($params, $compiler) {
    return "safe_url(ASSETS_URL." . $params [0] . ",true)";
}

<<<<<<< HEAD
=======
function smarty_modifiercompiler_module($params, $compiler) {
    return "safe_url(MODULE_URL." . $params [0] . ',true)';
}

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
function smarty_modifiercompiler_app($params, $compiler) {
    $params = smarty_argstr ( $params );
    return "Router::url($params)";
}

function smarty_modifiercompiler_base($params, $compiler) {
    $page = array_shift ( $params );
    $output = "safe_url({$page},true)";
    return $output;
}

function smarty_modifiercompiler_rstr($params, $compiler) {
    $str = array_shift ( $params );
    $cnt = 10;
    if (! empty ( $params )) {
        $cnt = intval ( array_shift ( $params ) );
    }
    $append = "''";
    if (! empty ( $params )) {
        $append = array_shift ( $params );
    }
    
    return "{$str}.{$append}.rand_str({$cnt}, 'a-z,A-Z')";
}

function smarty_modifiercompiler_rnum($params, $compiler) {
    $str = array_shift ( $params );
    $cnt = 10;
    if (! empty ( $params )) {
        $cnt = intval ( array_shift ( $params ) );
    }
    $append = "''";
    if (! empty ( $params )) {
        $append = array_shift ( $params );
    }
    
    return "{$str}.{$append}.rand_str({$cnt}, '0-9')";
}

function smarty_modifiercompiler_timediff($params, $compiler) {
    $cnt = time ();
    if (! empty ( $params )) {
        $cnt = array_shift ( $params );
    }
    return "timediff({$cnt})";
}

function smarty_modifiercompiler_timeread($params, $compiler) {
    return "readable_date({$params[0]})";
}

/**
 * Smarty checked modifier plugin.
 *
 * <code>
 * {'0'|checked:$value}
 * </code>
 *
 *
 * Type: modifier<br>
 * Name: checked<br>
 * Purpose: 根据值输出checked="checked"
 *
 * @param Smarty $compiler
 * @return string with compiled code
 */
function smarty_modifiercompiler_checked($value, $compiler) {
    return "((is_array($value[1]) && in_array($value[0],$value[1]) ) || $value[0] == $value[1])?'checked = \"checked\"' : ''";
}

/**
 * Smarty status modifier plugin.
 *
 * <code>
 * {value|status:list}
 * </code>
 *
 *
 * Type: modifier<br>
 * Name: status<br>
 * Purpose: 将值做为LIST中的KEY输出LIST对应的值
 *
 * @param Smarty $compiler
 * @return string with compiled code
 */
function smarty_modifiercompiler_status($status, $compiler) {
    if (count ( $status ) < 2) {
        trigger_error ( 'error usage of status', E_USER_WARNING );
        return "'error usage of status'";
    }
    $key = "$status[0]";
    $status_str = "$status[1]";
    $output = "$status_str" . "[$key]";
    return $output;
}

function smarty_modifiercompiler_random($ary, $compiler) {
    if (count ( $ary ) < 1) {
        trigger_error ( 'error usage of random', E_USER_WARNING );
        return "'error usage of random'";
    }
    $output = "is_array({$ary[0]})?{$ary[0]}[array_rand({$ary[0]})]:''";
    return $output;
}

/**
 * Smarty params modifier plugin.
 *
 * <code>
 * {url|params:[args]}
 * </code>
 *
 *
 * Type: modifier<br>
 * Name: params<br>
 * Purpose: 为URL添加或删除参数
 *
 * @see build_page_url()
 * @param Smarty $compiler
 * @return string with compiled code
 */
function smarty_modifiercompiler_params($ary, $compiler) {
    if (count ( $ary ) < 1) {
        trigger_error ( 'error usage of params', E_USER_WARNING );
        return "'error usage of params'";
    }
    $url = array_shift ( $ary );
    $args = empty ( $ary ) ? array () : smarty_argstr ( $ary );
    $output = "build_page_url($url,$args)";
    return $output;
}

function smarty_modifiercompiler_render($ary, $compiler) {
    if (count ( $ary ) < 1) {
        trigger_error ( 'error usage of render', E_USER_WARNING );
        return "''";
    }
    $render = $ary [0];
    array_shift ( $ary );
    $args = empty ( $ary ) ? '' : smarty_argstr ( $ary );
    return "{$render} instanceof Renderable?{$render}->render($args):{$render}";
}

function smarty_modifiercompiler_media($params, $compiler) {
    return 'the_media_src(' . $params [0] . ')';
}
<<<<<<< HEAD
=======

function smarty_modifiercompiler_tags($params, $compiler) {
    return 'TagForm::applyTags (' . $params [0] . ')';
}
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
