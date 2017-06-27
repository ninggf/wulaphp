<?php

/**
 * 从数组取值，如果数组中无指定key，则返回默认值.
 *
 * @param string $name
 * @param array  $array
 * @param mixed  $default
 *
 * @return mixed
 */
function aryget($name, $array, $default = '') {
	if (isset ($array [ $name ])) {
		return $array [ $name ];
	}

	return $default;
}

/**
 * 将以'，',' ','　','-',';','；','－'分隔的字符串转换成以逗号分隔的字符.
 *
 * @param string $string
 *
 * @return string
 */
function pure_comman_string($string) {
	if ($string) {
		return trim(trim(str_replace(array('，', ' ', '　', '-', ';', '；', '－'), ',', $string)), ',');
	}

	return '';
}

/**
 * 判断$tag是否在A标签中或是某个标签的属性.
 *
 * @param string $content
 * @param string $tag
 *
 * @return bool
 */
function in_atag($content, $tag) {
	$pos = strpos($content, $tag);
	if ($pos === false) {
		return false;
	}
	// 是否是某一个标签的属性
	$search = '`<[^>]*?' . preg_quote($tag, '`') . '[^>]*?>`ui';
	if (preg_match($search, $content)) {
		return true;
	}
	$pos  = strlen($content) - $pos;
	$spos = strripos($content, '<a', -$pos);
	$epos = strripos($content, '</a', -$pos);
	// 没有a标签
	if ($spos === false) {
		return false;
	}
	// 前边的a标签已经关掉
	if ($epos !== false && $epos > $spos) {
		return false;
	}

	return true;
}

/**
 * covert the charset of filename to UTF-8.
 *
 * @param string $filename
 *
 * @return string
 */
function thefilename($filename) {
	$encode = mb_detect_encoding($filename, "UTF-8,GBK,GB2312,BIG5,ISO-8859-1");
	if ($encode != 'UTF-8') {
		$filename = mb_convert_encoding($filename, "UTF-8", $encode);
	}

	return $filename;
}

/**
 * Set HTTP status header.
 *
 * @since 1.0
 *
 * @param int $header HTTP status code
 *
 */
function status_header($header) {
	$text = get_status_header_desc($header);

	if (empty ($text)) {
		return;
	}
	$protocol = $_SERVER ["SERVER_PROTOCOL"];
	if ('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol) {
		$protocol = 'HTTP/1.0';
	}

	$status_header = "$protocol $header $text";

	@header($status_header, true, $header);
	if (php_sapi_name() == 'cgi-fcgi') {
		@header("Status: $header $text");
	}
}

/**
 * Retrieve the description for the HTTP status.
 *
 * @since 1.0
 *
 * @param int $code HTTP status code.
 *
 * @return string Empty string if not found, or description if found.
 */
function get_status_header_desc($code) {
	global $output_header_to_desc;

	$code = abs(intval($code));

	if (!isset ($output_header_to_desc)) {
		$output_header_to_desc = array(100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Reserved', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Page Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 426 => 'Upgrade Required', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 510 => 'Not Extended');
	}
	if (isset ($output_header_to_desc [ $code ])) {
		return $output_header_to_desc [ $code ];
	} else {
		return '';
	}
}

function trailingslashit($string) {
	return untrailingslashit($string) . '/';
}

function untrailingslashit($string) {
	return rtrim($string, '/\\');
}

/**
 * 去除文件名中不合法的字符.
 *
 * @param string $filename
 *
 * @return string
 */
function sanitize_file_name($filename) {
	$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0));
	$filename      = str_replace($special_chars, '', $filename);
	$filename      = preg_replace('/[\s-]+/', '-', $filename);
	$filename      = trim($filename, '.-_');
	$parts         = explode('.', $filename);
	if (count($parts) <= 2) return $filename;
	$filename  = array_shift($parts);
	$extension = array_pop($parts);
	$mimes     = array('tmp', 'txt', 'jpg', 'gif', 'png', 'rar', 'zip', 'gzip', 'ppt');
	foreach (( array )$parts as $part) {
		$filename .= '.' . $part;
		if (preg_match('/^[a-zA-Z]{2,5}\d?$/', $part)) {
			$allowed = false;
			foreach ($mimes as $ext_preg => $mime_match) {
				$ext_preg = '!(^' . $ext_preg . ')$!i';
				if (preg_match($ext_preg, $part)) {
					$allowed = true;
					break;
				}
			}
			if (!$allowed) $filename .= '_';
		}
	}
	$filename .= '.' . $extension;

	return $filename;
}

/**
 * 获取唯一文件名.
 *
 * @param string        $dir
 * @param string        $filename
 * @param callable|null $unique_filename_callback
 *
 * @return string
 */
function unique_filename($dir, $filename, $unique_filename_callback = null) {
	$filename = sanitize_file_name($filename);
	$info     = pathinfo($filename);
	$ext      = !empty ($info ['extension']) ? '.' . $info ['extension'] : '';
	$name     = basename($filename, $ext);
	if ($name === $ext) {
		$name = '';
	}
	if ($unique_filename_callback && is_callable($unique_filename_callback)) {
		$filename = $unique_filename_callback ($dir, $name);
	} else {
		$number = 0;
		if ($ext && strtolower($ext) != $ext) {
			$ext2      = strtolower($ext);
			$filename2 = preg_replace('|' . preg_quote($ext) . '$|', $ext2, $filename);
			while (file_exists($dir . "/$filename") || file_exists($dir . "/$filename2")) {
				$new_number = $number + 1;
				$filename   = str_replace("$number$ext", "$new_number$ext", $filename);
				$filename2  = str_replace("$number$ext2", "$new_number$ext2", $filename2);
				$number     = $new_number;
			}

			return $filename2;
		}
		while (file_exists($dir . "/$filename")) {
			if ('' == "$number$ext") {
				$filename = $filename . ++$number . $ext;
			} else {
				$filename = str_replace("$number$ext", ++$number . $ext, $filename);
			}
		}
	}

	return $filename;
}

/**
 * 查找文件.
 *
 * @param string   $dir       起始目录
 * @param string   $pattern   合法的正则表达式,此表达式只用于文件名
 * @param array    $excludes  不包含的目录名
 * @param bool|int $recursive 是否递归查找
 * @param int      $stop      递归查找层数
 *
 * @return array 查找到的文件
 */
function find_files($dir = '.', $pattern = '', $excludes = array(), $recursive = 0, $stop = 0) {
	$files = array();
	$dir   = trailingslashit($dir);
	if (is_dir($dir)) {
		$fhd = @opendir($dir);
		if ($fhd) {
			$excludes  = is_array($excludes) ? $excludes : array();
			$_excludes = array_merge($excludes, array('.', '..'));
			while (($file = readdir($fhd)) !== false) {
				if ($recursive && is_dir($dir . $file) && !in_array($file, $_excludes)) {
					if ($stop == 0 || $recursive <= $stop) {
						$files = array_merge($files, find_files($dir . $file, $pattern, $excludes, $recursive + 1, $stop));
					}
				}
				if (is_file($dir . $file) && @preg_match($pattern, $file)) {
					$files [] = $dir . $file;
				}
			}
			@closedir($fhd);
		}
	}

	return $files;
}

/**
 * 删除目录及其内容,如果$keep为true则将目录本身也删除.
 *
 * @param string $dir
 * @param bool   $keep
 *
 * @return bool
 */
function rmdirs($dir, $keep = true) {
	$hd = @opendir($dir);
	if ($hd) {
		while (($file = readdir($hd)) != false) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (is_dir($dir . DS . $file)) {
				rmdirs($dir . DS . $file, false);
			} else {
				@unlink($dir . DS . $file);
			}
		}
		closedir($hd);
		if (!$keep) {
			@rmdir($dir);
		}
	}

	return true;
}

/**
 * 只保留URL中部分参数.
 *
 * @param string $url
 * @param array  $include 要保留的参数
 *
 * @return string
 */
function keepargs($url, $include = array()) {
	$urls = explode('?', $url);
	if (count($urls) < 2) {
		return $url;
	}
	$kargs = array();
	foreach ($include as $arg) {
		if (preg_match('/' . $arg . '=([^&]+)/', $urls [1], $m)) {
			$kargs [] = $m [0];
		}
	}
	if (!empty ($kargs)) {
		$urls [1] = implode('&', $kargs);

		return implode('?', $urls);
	} else {
		return $urls [0];
	}
}

/**
 * 删除URL中的参数.
 *
 * @param string $url
 * @param array  $exclude 要删除的参数
 *
 * @return string
 */
function unkeepargs($url, $exclude = array()) {
	$regex = array();
	$rpm   = array();
	if (is_string($exclude)) {
		$exclude = array($exclude);
	}
	foreach ($exclude as $ex) {
		$regex [] = '/&?' . $ex . '=[^&]*/';
		$rpm []   = '';
	}

	return preg_replace($regex, $rpm, $url);
}

/**
 * 从SESSION中取值.
 *
 * 如果未设置,则返回默认值 $default
 *
 * @param string $name    值名
 * @param mixed  $default 默认值
 *
 * @return mixed SESSION中的值
 */
function sess_get($name, $default = "") {
	if (isset ($_SESSION [ $name ])) {
		return $_SESSION [ $name ];
	}

	return $default;
}

/**
 * 从SESSION中删除变量$name,并将该变量值返回.
 *
 * @param string $name
 * @param string $default
 *
 * @return mixed
 */
function sess_del($name, $default = '') {
	$value = sess_get($name, $default);
	if (isset ($_SESSION [ $name ])) {
		$_SESSION [ $name ] = null;
		unset ($_SESSION [ $name ]);
	}

	return $value;
}

/**
 * 取数据.
 *
 * @param string $name
 * @param string $default
 * @param bool   $xss_clean
 *
 * @return mixed
 */
function rqst($name, $default = '', $xss_clean = true) {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}

	return $__rqst->get($name, $default, $xss_clean);
}

/**
 * 一次取多个值.
 *
 * @param array $names 表单中的字段名.
 * @param bool  $xss_clean
 * @param array $map   表单字段与结果字段的映射
 *
 * @return array
 */
function rqsts($names, $xss_clean = true, $map = []) {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}
	$rqts = [];
	foreach ($names as $key => $default) {
		if (is_numeric($key)) {
			$fname   = $default;
			$default = '';
		} else {
			$fname = $key;
		}
		$rname          = isset($map[ $fname ]) ? $map[ $fname ] : $fname;
		$rqts[ $rname ] = $__rqst->get($fname, $default, $xss_clean);
	}

	return $rqts;
}

/**
 * @param string $name
 * @param string $default
 *
 * @return mixed
 */
function arg($name, $default = '') {
	global $__rqst;
	if (defined('ARTISAN_TASK_PID')) {
		$__rqst = \wulaphp\io\Request::getInstance();
	} else if (!$__rqst) {
		$__rqst = wulaphp\io\Request::getInstance();
	}

	return $__rqst->get($name, $default, false);
}

/**
 * 是否有该请求数据.
 *
 * @param string $name
 *
 * @return bool
 */
function rqset($name) {
	return isset ($_REQUEST[ $name ]);
}

/**
 * 取int型参数。
 *
 * @param string $name
 * @param int    $default
 *
 * @return int
 */
function irqst($name, $default = 0) {
	return intval(rqst($name, $default, true));
}

/**
 * 取float型参数.
 *
 * @param string $name
 * @param int    $default
 *
 * @return float
 */
function frqst($name, $default = 0) {
	return floatval(rqst($name, $default, true));
}

/**
 * 安全ID.
 *
 * @param string  $ids   以$sp分隔的id列表,只能是大与0的整形.
 * @param string  $sp    分隔符.
 * @param boolean $array 是否返回数组.
 *
 * @return mixed
 */
function safe_ids($ids, $sp = ',', $array = false) {
	if (empty ($ids)) {
		return $array ? array() : '';
	}
	$_ids = explode($sp, $ids);
	$ids  = array();
	foreach ($_ids as $id) {
		if (preg_match('/^[1-9]\d*$/', $id)) {
			$ids [] = intval($id);
		}
	}
	if ($array === false) {
		return empty ($ids) ? '' : implode($sp, $ids);
	} else {
		return empty ($ids) ? array() : $ids;
	}
}

/**
 * 安全ID.
 *
 * @param string $ids 要处理的ids.
 * @param string $sp  分隔字符，默认为','.
 *
 * @return array
 */
function safe_ids2($ids, $sp = ',') {
	return safe_ids($ids, $sp, true);
}

/**
 * 可读的size.
 *
 * @param int $size
 *
 * @return string
 */
function readable_size($size) {
	$size = intval($size);
	if ($size < 1024) {
		return $size . 'B';
	} else if ($size < 1048576) {
		return number_format($size / 1024, 2) . 'K';
	} else if ($size < 1073741824) {
		return number_format($size / 1048576, 2) . 'M';
	} else {
		return number_format($size / 1073741824, 2) . 'G';
	}
}

function readable_num($size) {
	$size = intval($size);
	if ($size < 1000) {
		return $size;
	} else if ($size < 10000) {
		return number_format($size / 1000, 2) . 'K';
	} else if ($size < 10000000) {
		return number_format($size / 10000, 2) . 'W';
	} else {
		return number_format($size / 10000000, 2) . 'KW';
	}
}

function readable_date($sec, $text = array('s' => '秒', 'm' => '分', 'h' => '小时', 'd' => '天')) {
	$size = intval($sec);
	if ($size == 0) {
		return '';
	} else if ($size < 60) {
		return $size . $text ['s'];
	} else if ($size < 3600) {
		return floor($size / 60) . $text ['m'] . readable_date(fmod($size, 60));
	} else if ($size < 86400) {
		return floor($size / 3600) . $text ['h'] . readable_date(fmod($size, 3600));
	} else {
		return floor($size / 86400) . $text ['d'] . readable_date(fmod($size, 86400));
	}
}

/**
 * 合并$base与$arr.
 *
 * @param mixed $base
 * @param array $arr
 *
 * @return array 如果$base为空或$base不是一个array则直接返回$arr,反之返回array_merge($base,$arr)
 */
function array_merge2($base, $arr) {
	if (empty ($base) || !is_array($base)) {
		return $arr;
	}
	if (empty ($arr) || !is_array($arr)) {
		return $base;
	}

	return array_merge($base, $arr);
}

function get_query_string() {
	$query_str = $_SERVER ['QUERY_STRING'];
	if ($query_str) {
		parse_str($query_str, $args);
		unset ($args ['preview']);
		$query_str = http_build_query($args);
	}

	return empty ($query_str) ? '' : '?' . rtrim($query_str, '=');
}

/**
 * 记录debug信息.
 *
 * @param string $message
 * @param string $file
 */
function log_debug($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_DEBUG, $file);
}

/**
 * 记录info信息.
 *
 * @param string $message
 * @param string $file
 */
function log_info($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_INFO, $file);
}

/**
 * 记录warn信息.
 *
 * @param string $message
 * @param string $file
 */
function log_warn($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_WARN, $file);
}

/**
 * 记录error信息.
 *
 * @param string $message
 * @param string $file
 */
function log_error($message, $file = '') {
	$trace = debug_backtrace();
	log_message($message, $trace, DEBUG_ERROR, $file);
}

/**
 * 为url添加参数。
 *
 * @param string $url
 * @param array  $args
 *
 * @return string
 */
function url_append_args($url, $args) {
	if (strpos($url, '?') === false) {
		return $url . '?' . http_build_query($args);
	} else {
		return $url . '&' . http_build_query($args);
	}
}

/**
 * 将array的key/value通过$sep连接成一个字符串.
 *
 * @param array  $ary
 * @param string $concat 连接符
 * @param bool   $quote  连接时值是否用双引号包裹.
 * @param string $sep    组连接符
 *
 * @return string
 */
function ary_kv_concat(array $ary, $concat = '=', $quote = true, $sep = ' ') {
	if (empty ($ary)) {
		return '';
	}
	$quote   = $quote ? '"' : '';
	$tmp_ary = array();
	foreach ($ary as $name => $val) {
		$name       = trim($name);
		$tmp_ary [] = $name . $concat . "{$quote}{$val}{$quote}";
	}

	return implode($sep, $tmp_ary);
}

/**
 * 合并二个数组，并将对应值通过$sep进行连结(concat).
 *
 * @param array  $ary1 被加数组.
 * @param array  $ary2 数组.
 * @param string $sep  相加时的分隔符.
 *
 * @return array 合并后的数组.
 */
function ary_concat(array $ary1, array $ary2, $sep = ' ') {
	foreach ($ary2 as $key => $val) {
		if (isset ($ary1 [ $key ])) {
			if (is_array($ary1 [ $key ]) && is_array($val)) {
				$ary1 [ $key ] = ary_concat($ary1 [ $key ], $val);
			} else if (is_array($ary1 [ $key ]) && !is_array($val)) {
				$ary1 [ $key ] [] = $val;
			} else if (!is_array($ary1 [ $key ]) && is_array($val)) {
				$val []        = $ary1 [ $key ];
				$ary1 [ $key ] = $val;
			} else {
				$ary1 [ $key ] = $ary1 [ $key ] . $sep . $val;
			}
		} else {
			$ary1 [ $key ] = $val;
		}
	}

	return $ary1;
}

/**
 * log.
 *
 * @param string $message
 * @param array  $trace_info
 * @param int    $level debug,info,warn,error
 * @param string $file
 *
 * @filter logger\getLogger $logger $level $file
 */
function log_message($message, $trace_info, $level, $file = 'wula') {
	static $loggers = [];
	if (!isset($loggers[ $level ][ $file ])) {
		//获取日志器.
		$log = apply_filter('logger\getLogger', new \wulaphp\util\CommonLogger($file), $level, $file);
		if ($log instanceof Psr\Log\LoggerInterface) {
			$logger = $log;
		} else {
			$logger = null;
		}
		$loggers[ $level ][ $file ] = $logger;
	}

	if (empty ($trace_info)) {
		return;
	}

	if ($level >= DEBUG && $loggers[ $level ][ $file ]) {
		$loggers[ $level ][ $file ]->log($level, $message, $trace_info);
	}
}

/**
 * 生成随机字符串.
 *
 * @param int    $len
 * @param string $chars
 *
 * @return string
 */
function rand_str($len = 8, $chars = "a-z,0-9,$,_,!,@,#,=,~,$,%,^,&,*,(,),+,?,:,{,},[,],A-Z") {
	$characters  = explode(',', $chars);
	$num         = count($characters);
	$array_allow = [];
	for ($i = 0; $i < $num; $i++) {
		if (substr_count($characters [ $i ], '-') > 0) {
			$character_range = explode('-', $characters [ $i ]);
			$max             = ord($character_range [1]);
			for ($j = ord($character_range [0]); $j <= $max; $j++) {
				$array_allow [] = chr($j);
			}
		} else {
			$array_allow [] = $array_allow [ $i ];
		}
	}

	// 生成随机字符串
	mt_srand(( double )microtime() * 1000000);
	$code = array();
	$i    = 0;
	while ($i < $len) {
		$index   = mt_rand(0, count($array_allow) - 1);
		$code [] = $array_allow [ $index ];
		$i++;
	}

	return implode('', $code);
}

/**
 * 来自ucenter的加密解密函数.
 *
 * @param string $string    要解（加）密码字串
 * @param string $operation DECODE|ENCODE 解密|加密
 * @param string $key       密码
 * @param int    $expiry    超时
 *
 * @return string
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;

	$key  = md5($key ? $key : rand_str(3));
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey   = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);

	$string        = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);

	$result = '';
	$box    = range(0, 255);

	$rndkey = array();
	for ($i = 0; $i <= 255; $i++) {
		$rndkey [ $i ] = ord($cryptkey [ $i % $key_length ]);
	}

	for ($j = $i = 0; $i < 256; $i++) {
		$j          = ($j + $box [ $i ] + $rndkey [ $i ]) % 256;
		$tmp        = $box [ $i ];
		$box [ $i ] = $box [ $j ];
		$box [ $j ] = $tmp;
	}

	for ($a = $j = $i = 0; $i < $string_length; $i++) {
		$a          = ($a + 1) % 256;
		$j          = ($j + $box [ $a ]) % 256;
		$tmp        = $box [ $a ];
		$box [ $a ] = $box [ $j ];
		$box [ $j ] = $tmp;
		$result     .= chr(ord($string [ $i ]) ^ ($box [ ($box [ $a ] + $box [ $j ]) % 256 ]));
	}

	if ($operation == 'DECODE') {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace('=', '', base64_encode($result));
	}
}

/**
 *
 * @param string $versions format:[(min,max)]
 *
 * @return array array(min,minop,max,maxop)
 */
function parse_version_pair($versions) {
	$rst = array(false, '', false, '');
	if (preg_match('#^([\[\(])(.*?),(.*?)([\]\)])$#', $versions, $m)) {
		if ($m [2]) {
			$rst [0] = $m [2];
			if ($m [1] == '[') {
				$rst [1] = '<=';
			} else {
				$rst [1] = '<';
			}
		}
		if ($m [3]) {
			$rst [2] = $m [3];
			if ($m [4] == ']') {
				$rst [3] = '>=';
			} else {
				$rst [3] = '>';
			}
		}
	}

	return $rst;
}

/**
 * 从数据$ary取数据并把它从原数组中删除.
 *
 * @param array $ary
 *
 * @return array
 * @since 1.0.3
 */
function get_then_unset(&$ary) {
	$rtnAry = array();
	$cnt    = func_num_args();
	if (is_array($ary) && $ary && $cnt > 1) {
		for ($i = 1; $i < $cnt; $i++) {
			$arg = func_get_arg($i);
			if (isset ($ary [ $arg ])) {
				$rtnAry [] = $ary [ $arg ];
				unset ($ary [ $arg ]);
			} else {
				$rtnAry [] = '';
			}
		}
	}

	return $rtnAry;
}

function html_escape($string, $esc_type = 'html', $char_set = null, $double_encode = true) {

	if (!$char_set) {
		$char_set = 'UTF-8';
	}

	switch ($esc_type) {
		case 'html' :
		case 'htmlall' :
			$string = htmlspecialchars($string, ENT_QUOTES, $char_set, $double_encode);

			return $string;
		case 'url' :
			return rawurlencode($string);

		case 'urlpathinfo' :
			return str_replace('%2F', '/', rawurlencode($string));

		case 'quotes' :
			// escape unescaped single quotes
			return preg_replace("%(?<!\\\\)'%", "\\'", $string);

		case 'hex' :
			// escape every byte into hex
			// Note that the UTF-8 encoded character ä will be represented as %c3%a4
			$return  = '';
			$_length = strlen($string);
			for ($x = 0; $x < $_length; $x++) {
				$return .= '%' . bin2hex($string [ $x ]);
			}

			return $return;

		case 'javascript' :
			// escape quotes and backslashes, newlines, etc.
			return strtr($string, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/'));

		default :
			return $string;
	}
}

/**
 * 时间差
 *
 * @param integer $time
 *
 * @return string
 */
function timediff($time) {
	static $ctime = false;
	if ($ctime === false) {
		$ctime = time();
	}
	$d = $ctime - $time;
	if ($d < 60) {
		return _('刚刚');
	} else if ($d < 3600) {
		$it = floor($d / 60);

		return _($it . '分钟前');
	} else if ($d < 86400) {
		$it = floor($d / 3600);

		return _($it . '小时前');
	} else if ($d < 604800) {
		$it = floor($d / 86400);

		return _($it . '天前');
	} else if ($d < 2419200) {
		$it = floor($d / 604800);

		return _($it . '周前');
	} else {
		$it = floor($d / 2592000);

		return _($it . '月前');
	}
}

/**
 * 将目录$path压缩到$zipFileName.
 *
 * @param string $zipFileName 文件名.
 * @param string $path        要压缩的路径.
 *
 * @return boolean Returns true on success or false on failure.
 */
function zipit($zipFileName, $path) {
	if (!file_exists($path)) {
		return false;
	}
	$zip = new ZipArchive ();
	if ($zip->open($zipFileName, ZipArchive::OVERWRITE)) {
		$dir_iterator = new RecursiveDirectoryIterator ($path, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator     = new RecursiveIteratorIterator ($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
		$success      = true;
		foreach ($iterator as $file) {
			if (is_dir($file)) {
				$dest = str_replace($path, '', $file);
				if (!$zip->addEmptyDir($dest)) {
					$success = false;
					break;
				}
			} else {
				$dest = str_replace($path, '', $file);
				if (!$zip->addFile($file, $dest)) {
					$success = false;
					break;
				}
			}
		}
		$zip->close();
		if (!$success) {
			@unlink($zipFileName);
		}

		return $success;
	}

	return false;
}

/**
 * 从$str中截取$str1与$str2之间的字符串.
 *
 * @param string  $str
 * @param string  $str1
 * @param string  $str2
 * @param boolean $include_str1
 *
 * @return string
 */
function inner_str($str, $str1, $str2, $include_str1 = true) {
	if (!$str || !$str1 || !$str2) {
		return null;
	}
	$s    = $str1;
	$e    = $str2;
	$pos1 = strpos($str, $s);
	$pos2 = strpos($str, $e, $pos1 + strlen($s) + 1);
	if ($pos1 !== false && !$include_str1) {
		$pos1 += strlen($s);
	}
	if ($pos1 !== false && $pos2 !== false && $pos2 > $pos1) {
		$cnt = substr($str, $pos1, $pos2 - $pos1);

		return $cnt;
	} else {
		return null;
	}
}

/**
 * 得到session名.
 *
 * @return mixed
 * @filter  get_session_name session_name
 */
function get_session_name() {
	return apply_filter('get_session_name', 'phpsid');
}

/**
 * 生成SQL中不可变字符.
 *
 * @param string $val
 * @param string $alias
 *
 * @return \wulaphp\db\sql\ImmutableValue
 */
function imv($val, $alias = null) {
	return new \wulaphp\db\sql\ImmutableValue ($val, $alias);
}

/**
 * 去除字符串中的所有html标签,换行,空格等.
 *
 * @param string $text
 *
 * @return string
 */
function cleanhtml2simple($text) {
	$text = str_ireplace(array('[page]', ' ', '　', "\t", "\r", "\n", '&nbsp;'), '', $text);
	$text = preg_replace('#</?[a-z0-9][^>]*?>#msi', '', $text);

	return $text;
}

/**
 * 取当前用户的通行证.
 *
 * @param string $type 通行证类型.
 *
 * @return \wulaphp\auth\Passport
 */
function whoami($type = 'default') {
	return \wulaphp\auth\Passport::get($type);
}

/**
 * 不要调用它.
 *
 * @param Throwable $e
 *
 * @deprecated
 */
function wula_exception_handler($e) {
	global $argv;
	if (!defined('DEBUG') || DEBUG < DEBUG_ERROR) {
		if ($argv) {
			echo $e->getMessage(), "\n";
			echo $e->getTraceAsString(), "\n";
		} else {
			status_header(500);
			$stack  = [];
			$msg    = $e->getMessage();
			$tracks = $e->getTrace();

			$f = $e->getFile();
			$l = $e->getLine();
			array_unshift($tracks, ['line' => $l, 'file' => $f, 'function' => '']);
			foreach ($tracks as $i => $t) {
				$tss     = ['<tr>'];
				$tss[]   = "<td bgcolor=\"#eeeeec\" align=\"center\">$i</i>";
				$tss[]   = "<td bgcolor=\"#eeeeec\">{$t['function']}( )</td>";
				$f       = str_replace(APPROOT, '', $t['file']);
				$tss[]   = "<td bgcolor=\"#eeeeec\">{$f}<b>:</b>{$t['line']}</td>";
				$tss []  = '</tr>';
				$stack[] = implode('', $tss);
			}
			$errorFile = file_get_contents(__DIR__ . '/debug.tpl');
			$errorFile = str_replace(['{$message}', '{$stackInfo}', '{$title}', '{$tip}', '{$cs}', '{$f}', '{$l}', '{$uri}'], [$msg, implode('', $stack), __('Oops'), __('Fatal error'), __('Call Stack'), __('Function'), __('Location'), \wulaphp\router\Router::getURI()], $errorFile);
			echo $errorFile;
			exit(0);
		}
	} else {
		log_error($e->getMessage() . "\n" . $e->getTraceAsString(), 'exceptions');
		if ($argv) {
			exit(1);
		} else {
			\wulaphp\io\Response::respond(500, $e->getMessage());
		}
	}
}

/**
 * 不要调用它.
 * @deprecated
 */
function wula_shutdown_function() {
	define('WULA_STOPTIME', microtime(true));
	fire('wula\stop');
}

/**
 * 抛出一个异常以终止程序运行.
 *
 * @param string $message
 *
 * @throws \Exception
 */
function throw_exception($message) {
	throw new Exception($message);
}

include WULA_ROOT . 'includes/plugin.php';
include WULA_ROOT . 'includes/kernelimpl.php';
include WULA_ROOT . 'includes/template.php';
// end of file functions.php