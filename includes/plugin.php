<?php
/**
 * 运行时插件
 *
 * @global array
 * @name   $__ksg_rtk_hooks
 * @var array
 */
$__ksg_rtk_hooks = [];
/**
 * 已经排序的插件
 *
 * @global array
 * @name   $__ksg_sorted_hooks
 * @var array
 */
$__ksg_sorted_hooks = [];
/**
 * 正在触发的HOOKS
 *
 * @global array
 * @name   $__ksg_triggering_hooks
 * @var array
 */
$__ksg_triggering_hooks = [];

/**
 * 注册一个HOOK的回调函数
 *
 * @param              $hook
 * @param mixed        $hook_func     回调函数
 * @param int          $priority      优先级
 * @param int          $accepted_args 接受参数个数
 *
 * @return boolean
 */
function bind($hook, $hook_func, $priority = 10, $accepted_args = 1) {
	global $__ksg_rtk_hooks, $__ksg_sorted_hooks;

	if (empty ($hook)) {
		log_error('the hook name must not be empty!', 'plugin');

		return false;
	}
	$hook     = __rt_real_hook($hook);
	$priority = $priority ? $priority : 10;
	if (is_string($hook_func) && $hook_func{0} == '&') {
		$hook_func = ltrim($hook_func, '&');
		$hook_func = [$hook_func, str_replace(['.', '\\', '/', '-'], '_', $hook)];
	}
	if (empty ($hook_func)) {
		log_error('the hook function must not be empty!', 'plugin');

		return false;
	}
	$idx                                              = __rt_hook_unique_id($hook_func);
	$__ksg_rtk_hooks [ $hook ] [ $priority ] [ $idx ] = ['func' => $hook_func, 'accepted_args' => $accepted_args];

	unset ($__ksg_sorted_hooks [ $hook ]);

	return true;
}

/**
 * 移除一个HOOK回调函数
 *
 * @param string $hook
 * @param mixed  $hook_func 回调函数
 * @param int    $priority  优先级
 *
 * @return boolean
 */
function unbind($hook, $hook_func, $priority = 10) {
	global $__ksg_rtk_hooks, $__ksg_sorted_hooks;
	$hook = __rt_real_hook($hook);
	$idx  = __rt_hook_unique_id($hook_func);

	$r = isset ($__ksg_rtk_hooks [ $hook ] [ $priority ] [ $idx ]);

	if (true === $r) {
		unset ($__ksg_rtk_hooks [ $hook ] [ $priority ] [ $idx ]);
		if (empty ($__ksg_rtk_hooks [ $hook ] [ $priority ])) {
			unset ($__ksg_rtk_hooks [ $hook ] [ $priority ]);
		}
		unset ($__ksg_sorted_hooks [ $hook ]);
	}

	return $r;
}

/**
 * 移除$hook对应的所有回调函数
 *
 * @param  string  $hook
 * @param bool|int $priority 优先级
 *
 * @return bool
 */
function unbind_all($hook, $priority = false) {
	global $__ksg_rtk_hooks, $__ksg_sorted_hooks;
	$hook = __rt_real_hook($hook);
	if (isset ($__ksg_rtk_hooks [ $hook ])) {
		if (false !== $priority && isset ($__ksg_rtk_hooks [ $hook ] [ $priority ])) {
			unset ($__ksg_rtk_hooks [ $hook ] [ $priority ]);
		} else {
			unset ($__ksg_rtk_hooks [ $hook ]);
		}
	}
	if (isset ($__ksg_sorted_hooks [ $hook ])) {
		unset ($__ksg_sorted_hooks [ $hook ]);
	}

	return true;
}

/**
 * 触发HOOK
 *
 * @global array $__ksg_rtk_hooks        系统所有HOOK的回调
 * @global array $__ksg_sorted_hooks     当前的HOOK回调是否已经排序
 * @global array $__ksg_triggering_hooks 正在执行的回调
 *
 * @param string $hook                   HOOK名称
 * @param mixed  $arg                    参数
 *
 * @return string
 */
function fire($hook, $arg = '') {
	global $__ksg_rtk_hooks, $__ksg_sorted_hooks, $__ksg_triggering_hooks;
	$hook                      = __rt_real_hook($hook);
	$__ksg_triggering_hooks [] = $hook;
	if (!isset ($__ksg_rtk_hooks [ $hook ])) { // 没有该HOOK的回调
		array_pop($__ksg_triggering_hooks);

		return '';
	}
	$args = [];
	if (is_array($arg) && 1 == count($arg) && is_object($arg [0])) { // array(&$this)
		$args [] = &$arg [0];
	} else {
		$args [] = $arg;
	}
	for ($a = 2; $a < func_num_args(); $a++) {
		$args [] = func_get_arg($a);
	}

	// 对hook的回调进行排序
	if (!isset ($__ksg_sorted_hooks [ $hook ])) {
		ksort($__ksg_rtk_hooks [ $hook ]);
		$__ksg_sorted_hooks [ $hook ] = true;
	}
	// 重置hook回调数组
	reset($__ksg_rtk_hooks [ $hook ]);
	@ob_start();
	try {
		do {
			foreach (( array )current($__ksg_rtk_hooks [ $hook ]) as $the_) {
				if (!is_null($the_ ['func'])) {
					if (is_array($the_['func'])) {
						\wulaphp\util\ObjectCaller::callClzMethod($the_['func'][0], $the_['func'][1], $args);
					} else if ($the_ ['func'] instanceof Closure) {
						$params = array_slice($args, 0, ( int )$the_ ['accepted_args']);
						$the_ ['func'](...$params);
					} else if (is_callable($the_ ['func'])) {
						$params = array_slice($args, 0, ( int )$the_ ['accepted_args']);
						$the_ ['func'](...$params);
					}
				}
			}
		} while (next($__ksg_rtk_hooks [ $hook ]) !== false);
	} catch (Exception $e) {

	}
	array_pop($__ksg_triggering_hooks);

	return @ob_get_clean();
}

/**
 * 调用与指定过滤器关联的HOOK
 *
 *
 * @param string $filter 过滤器名
 * @param mixed  $value
 *
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filter($filter, $value) {
	global $__ksg_rtk_hooks, $__ksg_sorted_hooks, $__ksg_triggering_hooks;
	$filter                    = __rt_real_hook($filter);
	$__ksg_triggering_hooks [] = $filter;

	if (!isset ($__ksg_rtk_hooks [ $filter ])) {
		array_pop($__ksg_triggering_hooks);

		return $value;
	}

	if (!isset ($__ksg_sorted_hooks [ $filter ])) {
		ksort($__ksg_rtk_hooks [ $filter ]);
		$__ksg_sorted_hooks [ $filter ] = true;
	}

	reset($__ksg_rtk_hooks [ $filter ]);

	$args = func_get_args();

	do {
		foreach (( array )current($__ksg_rtk_hooks [ $filter ]) as $the_) {
			if (!is_null($the_ ['func'])) {
				$args [1] = $value;
				if (is_array($the_['func'])) {
					$value = \wulaphp\util\ObjectCaller::callClzMethod($the_['func'][0], $the_['func'][1], array_slice($args, 1));
				} else if ($the_ ['func'] instanceof Closure) {
					$params = array_slice($args, 1, ( int )$the_ ['accepted_args']);
					$value  = $the_ ['func'](...$params);
				} else if (is_callable($the_ ['func'])) {
					$params = array_slice($args, 1, ( int )$the_ ['accepted_args']);
					$value  = $the_ ['func'](...$params);
				}
			}
		}
	} while (next($__ksg_rtk_hooks [ $filter ]) !== false);

	array_pop($__ksg_triggering_hooks);

	return $value;
}

/**
 * Check if any hook has been registered.
 *
 * @global array        $__ksg_rtk_hooks   Stores all of the hooks
 *
 * @param string        $hook
 * @param bool|callable $function_to_check optional. If specified, return the priority of that function on this hook or
 *                                         false if not attached.
 *
 * @global array        $__ksg_rtk_hooks   Stores all of the hooks
 * @see      wordpress has_filter
 * @internal param string $tag The name of the filter hook.
 * @return int boolean returns the priority on that hook for the specified function.
 */
function has_hook($hook, $function_to_check = false) {
	global $__ksg_rtk_hooks;
	$hook = __rt_real_hook($hook);
	$has  = !empty ($__ksg_rtk_hooks [ $hook ]);
	if (false === $function_to_check || false == $has) {
		return $has;
	}
	if (!$idx = __rt_hook_unique_id($function_to_check)) {
		return false;
	}
	foreach (( array )array_keys($__ksg_rtk_hooks [ $hook ]) as $priority) {
		if (isset ($__ksg_rtk_hooks [ $hook ] [ $priority ] [ $idx ])) {
			return $priority;
		}
	}

	return false;
}

/**
 * Build Unique ID for storage and retrieval.
 *
 * @see wordpress
 * @global array $__ksg_rtk_hooks Storage for all of the filters and actions
 * @staticvar $filter_id_count
 *
 * @param string $function
 *
 * @return string bool ID for usage as array key or false if $priority === false and $function is an object reference,
 *                and it does not already have a uniqe id.
 */
function __rt_hook_unique_id($function) {
	if (is_string($function)) {
		return $function;
	} else if (is_array($function)) {
		if (is_object($function [0])) {
			return spl_object_hash($function [0]) . $function [1];
		} else if (is_string($function [0])) {
			return $function [0] . $function [1];
		}
	} else if ($function instanceof Closure) {
		return spl_object_hash($function);
	}

	return false;
}

function __rt_real_hook($hook) {
	$hook = ucwords(ltrim($hook, '\\'), '\\');

	return lcfirst(str_replace('\\', '', $hook));
}
// end of file plugin.php