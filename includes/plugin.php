<?php
/**
 * 运行时插件
 *
 * @global array
 * @name $__ksg_rtk_hooks
 * @var array
 */
$__ksg_rtk_hooks = array ();
/**
 * 已经排序的插件
 *
 * @global array
 * @name $__ksg_sorted_hooks
 * @var array
 */
$__ksg_sorted_hooks = array ();
/**
 * 正在触发的HOOKS
 *
 * @global array
 * @name $__ksg_triggering_hooks
 * @var array
 */
$__ksg_triggering_hooks = array ();
/**
 * had been loaded files
 *
 * @global array
 * @name $__ksg_loaded_files
 * @var array
 */
$__ksg_loaded_files = array ();

/**
 * 注册一个HOOK的回调函数
 *
 * @param $hook
 * @param mixed $hook_func 回调函数
 * @param int $priority 优先级
 * @param int $accepted_args 接受参数个数
 * @global array 运行时插件回调
 * @global array 已排序插件
 * @internal param string $hook_name HOOK名称
 * @return boolean
 */
function bind($hook, $hook_func, $priority = 10, $accepted_args = 1) {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks;
    
    if (empty ( $hook )) {
        log_error ( 'the hook name must not be empty!' );
        return;
    }
    $priority = $priority ? $priority : 10;
    if (is_string ( $hook_func ) && $hook_func {0} == '&') {
        $hook_func = ltrim ( $hook_func, '&' );
        $hook_func = array ($hook_func,$hook );
    }
    if (empty ( $hook_func )) {
        log_error ( 'the hook function must not be empty!' );
        return;
    }
    $idx = __rt_hook_unique_id ( $hook, $hook_func, $priority );
    $__ksg_rtk_hooks [$hook] [$priority] [$idx] = array ('func' => $hook_func,'accepted_args' => $accepted_args);
    
    unset ( $__ksg_sorted_hooks [$hook] );
    return true;
}

/**
 * 移除一个HOOK回调函数
 *
 * @param $hook
 * @param mixed $hook_func 回调函数
 * @param int $priority 优先级
 * @global array 运行时插件回调
 * @global array 已排序插件
 * @internal param string $hook_name HOOK名称
 * @return boolean
 */
function unbind($hook, $hook_func, $priority = 10) {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks;
    
    $idx = __rt_hook_unique_id ( $hook, $hook_func, $priority );
    
    $r = isset ( $__ksg_rtk_hooks [$hook] [$priority] [$idx] );
    
    if (true === $r) {
        unset ( $__ksg_rtk_hooks [$hook] [$priority] [$idx] );
        if (empty ( $__ksg_rtk_hooks [$hook] [$priority] )) {
            unset ( $__ksg_rtk_hooks [$hook] [$priority] );
        }
        unset ( $__ksg_sorted_hooks [$hook] );
    }
    return $r;
}

/**
 * 移除$hook对应的所有回调函数
 *
 * @param $hook
 * @param bool|int $priority 优先级
 * @global array 运行时插件回调
 * @global array 已排序插件
 * @internal param string $hook_name HOOK名称
 * @return bool
 */
function unbind_all($hook, $priority = false) {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks;
    
    if (isset ( $__ksg_rtk_hooks [$hook] )) {
        if (false !== $priority && isset ( $__ksg_rtk_hooks [$hook] [$priority] )) {
            unset ( $__ksg_rtk_hooks [$hook] [$priority] );
        } else {
            unset ( $__ksg_rtk_hooks [$hook] );
        }
    }
    if (isset ( $__ksg_sorted_hooks [$hook] )) {
        unset ( $__ksg_sorted_hooks [$hook] );
    }
    return true;
}

/**
 * 触发HOOK
 *
 * @global array $__ksg_rtk_hooks 系统所有HOOK的回调
 * @global array $__ksg_sorted_hooks 当前的HOOK回调是否已经排序
 * @global array $__ksg_triggering_hooks 正在执行的回调
 * @param string $hook HOOK名称
 * @param mixed $arg 参数
 * @return string 如果HOOK的回调中有输出,则返回输出
 */
function fire($hook, $arg = "") {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks, $__ksg_triggering_hooks;
    
    $__ksg_triggering_hooks [] = $hook;
    if (! isset ( $__ksg_rtk_hooks [$hook] )) { // 没有该HOOK的回调
        array_pop ( $__ksg_triggering_hooks );
        return;
    }
    $args = array ();
    if (is_array ( $arg ) && 1 == count ( $arg ) && is_object ( $arg [0] )) { // array(&$this)
        $args [] = & $arg [0];
    } else {
        $args [] = $arg;
    }
    for($a = 2; $a < func_num_args (); $a ++) {
        $args [] = func_get_arg ( $a );
    }
    
    // 对hook的回调进行排序
    if (! isset ( $__ksg_sorted_hooks [$hook] )) {
        ksort ( $__ksg_rtk_hooks [$hook] );
        $__ksg_sorted_hooks [$hook] = true;
    }
    // 重置hook回调数组
    reset ( $__ksg_rtk_hooks [$hook] );
    
    do {
        foreach ( ( array ) current ( $__ksg_rtk_hooks [$hook] ) as $the_ ) {
            if (! is_null ( $the_ ['func'] )) {
                if (is_callable ( $the_ ['func'] )) {
                    call_user_func_array ( $the_ ['func'], array_slice ( $args, 0, ( int ) $the_ ['accepted_args'] ) );
                }
            }
        }
    } while ( next ( $__ksg_rtk_hooks [$hook] ) !== false );
    array_pop ( $__ksg_triggering_hooks );
}

/**
 * 参数以数组的方式传送
 *
 * @global array $__ksg_rtk_hooks 系统所有HOOK的回调
 * @global array $__ksg_sorted_hooks 当前的HOOK回调是否已经排序
 * @global array $__ksg_triggering_hooks 正在执行的回调
 * @see fire
 * @param string $hook HOOK
 * @param array $args 参数
 */
function fire_ref_array($hook, $args) {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks, $__ksg_triggering_hooks;
    
    $__ksg_triggering_hooks [] = $hook;
    // Do 'all' actions first
    if (isset ( $__ksg_rtk_hooks ['all'] )) {
        $all_args = func_get_args ();
        __rt_call_all_hook ( $all_args );
    }
    if (! isset ( $__ksg_rtk_hooks [$hook] )) { // 没有该HOOK的回调
        array_pop ( $__ksg_triggering_hooks );
        return;
    }
    // 对hook的回调进行排序
    if (! isset ( $__ksg_sorted_hooks [$hook] )) {
        ksort ( $__ksg_rtk_hooks [$hook] );
        $__ksg_sorted_hooks [$hook] = true;
    }
    // 重置hook回调数组
    reset ( $__ksg_rtk_hooks [$hook] );
    do {
        foreach ( ( array ) current ( $__ksg_rtk_hooks [$hook] ) as $the_ ) {
            if (! is_null ( $the_ ['func'] )) {
                if (is_callable ( $the_ ['func'] )) {
                    call_user_func_array ( $the_ ['func'], array_slice ( $args, 0, ( int ) $the_ ['accepted_args'] ) );
                }
            }
        }
    } while ( next ( $__ksg_rtk_hooks [$hook] ) !== false );
    array_pop ( $__ksg_triggering_hooks );
}

/**
 * 调用与指定过滤器关联的HOOK
 *
 *
 * @param string $filter 过滤器名
 * @param mixed $value
 * @global array $__ksg_rtk_hooks 系统所有HOOK的回调
 * @global array $__ksg_sorted_hooks 当前的HOOK回调是否已经排序
 * @global array $__ksg_triggering_hooks 正在执行的回调
 * @internal param mixed $var ....
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filter($filter, $value) {
    global $__ksg_rtk_hooks, $__ksg_sorted_hooks, $__ksg_triggering_hooks;
    
    $args = array ();
    $__ksg_triggering_hooks [] = $filter;
    
    if (isset ( $__ksg_rtk_hooks ['all'] )) {
        $args = func_get_args ();
        __rt_call_all_hook ( $args );
    }
    
    if (! isset ( $__ksg_rtk_hooks [$filter] )) {
        array_pop ( $__ksg_triggering_hooks );
        return $value;
    }
    
    if (! isset ( $__ksg_sorted_hooks [$filter] )) {
        ksort ( $__ksg_rtk_hooks [$filter] );
        $__ksg_sorted_hooks [$filter] = true;
    }
    
    reset ( $__ksg_rtk_hooks [$filter] );
    
    if (empty ( $args )) {
        $args = func_get_args ();
    }
    
    do {
        foreach ( ( array ) current ( $__ksg_rtk_hooks [$filter] ) as $the_ ) {
            if (! is_null ( $the_ ['func'] )) {
                $args [1] = $value;
                if (is_callable ( $the_ ['func'] )) {
                    $value = call_user_func_array ( $the_ ['func'], array_slice ( $args, 1, ( int ) $the_ ['accepted_args'] ) );
                }
            }
        }
    } while ( next ( $__ksg_rtk_hooks [$filter] ) !== false );
    
    array_pop ( $__ksg_triggering_hooks );
    
    return $value;
}

/**
 * 正在触发的HOOK(包括Filter)
 *
 * @global array
 * @return string the hook name whitch is triggering
 */
function triggering_hook() {
    global $__ksg_triggering_hooks;
    return end ( $__ksg_triggering_hooks );
}

/**
 * Check if any hook has been registered.
 *
 * @global array $__ksg_rtk_hooks Stores all of the hooks
 * @param string $hook
 * @param bool|callable $function_to_check optional. If specified, return the priority of that function on this hook or false if not attached.
 * @global array $__ksg_rtk_hooks Stores all of the hooks
 * @see wordpress has_filter
 * @internal param string $tag The name of the filter hook.
 * @return int boolean returns the priority on that hook for the specified function.
 */
function has_hook($hook, $function_to_check = false) {
    global $__ksg_rtk_hooks;
    
    $has = ! empty ( $__ksg_rtk_hooks [$hook] );
    if (false === $function_to_check || false == $has) {
        return $has;
    }
    if (! $idx = __rt_hook_unique_id ( $hook, $function_to_check, false )) {
        return false;
    }
    foreach ( ( array ) array_keys ( $__ksg_rtk_hooks [$hook] ) as $priority ) {
        if (isset ( $__ksg_rtk_hooks [$hook] [$priority] [$idx] )) {
            return $priority;
        }
    }
    return false;
}

/**
 * 调用 all HOOK 回调
 *
 * @global array
 * @param array $args 参数
 */
function __rt_call_all_hook($args) {
    global $__ksg_rtk_hooks, $__ksg_loaded_files;
    
    reset ( $__ksg_rtk_hooks ['all'] );
    do {
        foreach ( ( array ) current ( $__ksg_rtk_hooks ['all'] ) as $the_ ) {
            if (! is_null ( $the_ ['func'] )) {
                call_user_func_array ( $the_ ['func'], $args );
            }
        }
    } while ( next ( $__ksg_rtk_hooks ['all'] ) !== false );
}

/**
 * Build Unique ID for storage and retrieval.
 *
 * @see wordpress
 * @global array $__ksg_rtk_hooks Storage for all of the filters and actions
 * @staticvar $filter_id_count
 * @param string $hook_name
 * @param string $function
 * @param string $priority
 * @return string bool ID for usage as array key or false if $priority === false and $function is an object reference, and it does not already have a uniqe id.
 */
function __rt_hook_unique_id($hook_name, $function, $priority) {
    global $__ksg_rtk_hooks;
    static $filter_id_count = 0;
    
    if (is_string ( $function )) {
        return $function;
    } else if (is_object ( $function [0] )) {
        // Object Class Calling
        if (function_exists ( 'spl_object_hash' )) {
            return spl_object_hash ( $function [0] ) . $function [1];
        } else {
            $obj_idx = get_class ( $function [0] ) . $function [1];
            if (! isset ( $function [0]->wp_filter_id )) {
                if (false === $priority) {
                    return false;
                }
                $obj_idx .= isset ( $__ksg_rtk_hooks [$hook_name] [$priority] ) ? count ( ( array ) $__ksg_rtk_hooks [$hook_name] [$priority] ) : $filter_id_count;
                $function [0]->wp_filter_id = $filter_id_count;
                ++ $filter_id_count;
            } else {
                $obj_idx .= $function [0]->wp_filter_id;
            }
            
            return $obj_idx;
        }
    } else if (is_string ( $function [0] )) {
        // Static Calling
        return $function [0] . $function [1];
    }
    return false;
}
// end of file plugin.php