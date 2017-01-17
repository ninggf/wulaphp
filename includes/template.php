<?php
/**
 * merge arguments.
 *
 * @param array $args    the array to be merged
 * @param array $default the array to be merged with
 *
 * @return array the merged arguments array
 */
function merge_args($args, $default) {
	$_args = array();
	foreach ($args as $key => $val) {
		if (is_numeric($val) || is_bool($val) || !empty ($val)) {
			$_args [ $key ] = $val;
		}
	}
	foreach ($default as $key => $val) {
		if (!isset ($_args [ $key ])) {
			$_args [ $key ] = $val;
		}
	}

	return $_args;
}

/**
 * 解析smarty参数.
 *
 * 将参数中 '" 去除比,如 '1' 转换为1.
 *
 * @param array $args 参数数组
 *
 * @return array 解析后的参数
 */
function smarty_parse_args($args) {
	foreach ($args as $key => $value) {
		if (strpos($value, '_smarty_tpl->tpl_vars') !== false) {
			$args [ $key ] = trim($value, '\'"');
		}
	}

	return $args;
}

/**
 * 将smarty传过来的参数转换为可eval的字符串.
 *
 * @param array $args
 *
 * @return string
 */
function smarty_argstr($args) {
	$a = array();
	foreach ($args as $k => $v) {
		$v1 = trim($v);
		if (empty ($v1) && $v1 != '0' && $v1 != 0) {
			continue;
		}
		if ($v == false) {
			$a [] = "'$k'=>false";
		} else {
			$a [] = "'$k'=>$v";
		}
	}

	return 'array(' . implode(',', $a) . ')';
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
 *
 * @param array  $params 参数
 * @param Smarty $compiler
 *
 * @return string with compiled code
 */
function smarty_modifiercompiler_here($params, $compiler) {
	static $base = null;
	if ($base == null) {
		$base = str_replace(DS, '/', WWWROOT);
	}
	$tpl = str_replace(DS, '/', dirname($compiler->template->source->filepath));
	$tpl = str_replace($base, '', $tpl);
	$url = !empty ($tpl) ? trailingslashit($tpl) : '';

	return "WWWROOT_DIR.'{$url}'." . $params [0] . '';
}

function smarty_modifiercompiler_clean($params, $compiler) {
	return 'cleanhtml2simple(' . $params [0] . ')';
}

function smarty_modifiercompiler_rstr($params, $compiler) {
	$str = array_shift($params);
	$cnt = 10;
	if (!empty ($params)) {
		$cnt = intval(array_shift($params));
	}
	$append = "''";
	if (!empty ($params)) {
		$append = array_shift($params);
	}

	return "{$str}.{$append}.rand_str({$cnt}, 'a-z,A-Z')";
}

function smarty_modifiercompiler_rnum($params, $compiler) {
	$str = array_shift($params);
	$cnt = 10;
	if (!empty ($params)) {
		$cnt = intval(array_shift($params));
	}
	$append = "''";
	if (!empty ($params)) {
		$append = array_shift($params);
	}

	return "{$str}.{$append}.rand_str({$cnt}, '0-9')";
}

function smarty_modifiercompiler_timediff($params, $compiler) {
	$cnt = time();
	if (!empty ($params)) {
		$cnt = array_shift($params);
	}

	return "timediff({$cnt})";
}

function smarty_modifiercompiler_app($params, $compiler) {
	return "wulaphp\\app\\App::url({$params[0]})";
}

function smarty_modifiercompiler_action($params, $compiler) {
	return "wulaphp\\app\\App::action({$params[0]})";
}

function smarty_modifiercompiler_res($params, $compiler) {
	return "wulaphp\\app\\App::res({$params[0]})";
}

function smarty_modifiercompiler_assets($params, $compiler) {
	return "wulaphp\\app\\App::assets({$params[0]})";
}

function smarty_modifiercompiler_vendor($params, $compiler) {
	return "wulaphp\\app\\App::vendor({$params[0]})";
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
 * @param mixed  $value
 * @param Smarty $compiler
 *
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
 * @param mixed  $status
 * @param Smarty $compiler
 *
 * @return string with compiled code
 */
function smarty_modifiercompiler_status($status, $compiler) {
	if (count($status) < 2) {
		trigger_error('error usage of status', E_USER_WARNING);

		return "''";
	}
	$key        = "$status[0]";
	$status_str = "$status[1]";
	$output     = "$status_str" . "[$key]";

	return $output;
}

function smarty_modifiercompiler_random($ary, $compiler) {
	if (count($ary) < 1) {
		//trigger_error('error usage of random', E_USER_WARNING);

		return "''";
	}
	$output = "is_array({$ary[0]})?{$ary[0]}[array_rand({$ary[0]})]:''";

	return $output;
}

function smarty_modifiercompiler_render($ary, $compiler) {
	if (count($ary) < 1) {
		trigger_error('error usage of render', E_USER_WARNING);

		return "''";
	}
	$render = $ary [0];
	array_shift($ary);
	$args = empty ($ary) ? '' : smarty_argstr($ary);

	return "{$render} instanceof \\wulaphp\\mvc\view\\Renderable?{$render}->render($args):{$render}";
}

/**
 * the views in modules.
 *
 * @param array|string $data
 * @param string|array $tpl
 * @param array        $headers
 *
 * @return \wulaphp\mvc\view\SmartyView
 */
function view($data = array(), $tpl = '', $headers = array('Content-Type' => 'text/html')) {
	if (is_string($data)) {
		return new \wulaphp\mvc\view\SmartyView($tpl, $data, $headers);
	}

	return new \wulaphp\mvc\view\SmartyView($data, $tpl, $headers);
}

/**
 * @param string $tpl
 * @param array  $data
 * @param array  $headers
 *
 * @filter get_theme $theme
 * @filter get_custome_tplfile [$tpl,$theme], $data
 * @return \wulaphp\mvc\view\ThemeView
 */
function template($tpl, $data = array(), $headers = array('Content-Type' => 'text/html')) {
	static $called_funcs = [];
	$theme   = apply_filter('get_theme', 'default');
	$tplname = str_replace(array('/', '.'), '_', basename($tpl, '.tpl'));
	list($_tpl, $theme) = apply_filter('get_custome_tplfile', [$tpl, $theme], $data);
	$_tpl = $theme . DS . $_tpl;
	if (is_file(THEME_PATH . $_tpl)) {
		$tplfile = $_tpl;
	} else {
		$tplfile = 'default' . DS . $tpl;
		$theme   = 'default';
	}
	$template_func_file = THEME_PATH . $theme . DS . 'template.php';
	if (is_file($template_func_file)) {
		include_once $template_func_file;
		$func = $theme . '_' . $tplname . '_template_data';
		if (function_exists($func) && !isset($called_funcs[ $func ])) {
			$called_funcs[ $func ] = 1;
			$func ($data);
		}
		$func = $theme . '_template_data';
		if (function_exists($func) && !isset($called_funcs[ $func ])) {
			$called_funcs[ $func ] = 1;
			$func ($data);
		}
	}
	if ($theme != 'default') {
		$template_func_file = THEME_PATH . 'default' . DS . 'template.php';
		if (is_file($template_func_file)) {
			include_once $template_func_file;
			$func = 'default_' . $tplname . '_template_data';
			if (function_exists($func) && !isset($called_funcs[ $func ])) {
				$called_funcs[ $func ] = 1;
				$func ($data);

			}
			$func = 'default_template_data';
			if (function_exists($func) && !isset($called_funcs[ $func ])) {
				$called_funcs[ $func ] = 1;
				$func ($data);
			}
		}
	}
	$data ['_current_template'] = $tplfile;
	$data ['_theme_name']       = $theme;

	return new \wulaphp\mvc\view\ThemeView($data, $tplfile, $headers);
}

/**
 * 合并资源(JS or CSS).
 *
 * @param string $content
 * @param string $type js or css
 * @param string $ver  版本号
 *
 * @filter combinater\getPath file
 * @filter combinater\getURL WWWROOT_DIR
 * @return string
 */
function combinate_resources($content, $type, $ver) {
	if (APP_MODE == 'dev' || !\wulaphp\app\App::bcfg('resource.combinate')) {
		return $content;
	}
	if (!$content) {
		return '';
	}
	$md5      = md5($content . $ver);
	$file     = $type . DS . $md5 . '.' . $type;
	$path     = apply_filter('combinater\getPath', 'files');
	$url      = trailingslashit(apply_filter('combinater\getURL', WWWROOT_DIR) . $path) . $file . '?ver=' . $ver;
	$destFile = WWWROOT . $path . DS . $file;
	$dir      = dirname($destFile);
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}

	if (!is_dir($dir)) {
		return $content;
	}

	if ($type == 'css') {
		$reg = '#href\s*=\s*"([^"]+)"#i';
	} else {
		$reg = '#src\s*=\s*"([^"]+)"#i';
	}

	$files = [];
	if (preg_match_all($reg, $content, $ms)) {
		foreach ($ms[1] as $res) {
			$files[] = WWWROOT . substr($res, strlen(WWWROOT_DIR));
		}
	}

	if ($type == 'js') {
		\wulaphp\util\ResourceCombinater::combinateJS($files, $destFile);
		$tag = '<script type="text/javascript" src="' . $url . '"></script>';
	} else {
		\wulaphp\util\ResourceCombinater::combinateCSS($files, $destFile);
		$tag = '<link rel="stylesheet" type="text/css" href="' . $url . '">';
	}

	return $tag;
}

/**
 * 压缩
 *
 * @param string $content
 * @param string $type css or js
 *
 * @return string
 */
function minify_resources($content, $type) {
	static $cm = false;
	if (APP_MODE == 'dev' || !\wulaphp\app\App::bcfg('resource.minify')) {
		return $content;
	}
	if ($type == 'js') {
		return JSMin::minify($content);
	} else {
		if ($cm === false) {
			$cm = new CSSmin ();
		}

		return $cm->run($content);
	}
}