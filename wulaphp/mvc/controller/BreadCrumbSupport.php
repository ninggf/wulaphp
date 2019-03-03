<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\controller;

use wulaphp\mvc\view\View;
use wulaphp\router\Router;
use wulaphp\util\Annotation;

/**
 * 基于session的面包屑特性。不支持同一个登录(SESSION)开多窗口（会导致导航错乱）。
 * 使用方法:
 * 1. 在方法上添加crumb或crumbd注解
 * 2. crumb注解格式:
 *      2.1 @crumb 1
 *          2.1.1 此时可以通过设置crumbTitle来自定义标题
 *      2.2 @crumb 1 导航标题
 * 3. crumbd注解格式:
 *      3.1 @crumbd 用于ajax搜索时自动更新列表页参数
 * @package wulaphp\mvc\controller
 */
trait BreadCrumbSupport {
    /**
     * 后运行.
     *
     * @param string                 $action
     * @param \wulaphp\mvc\view\View $view
     * @param \ReflectionMethod      $method
     *
     * @return \wulaphp\mvc\view\View
     */
    protected function afterRunInBreadCrumbSupport($action, $view, $method) {
        if ($view instanceof View) {
            $breadCrumbs = sess_get('_wula_bdCbs_', []);
            $ann         = new Annotation($method);
            $crumb       = $ann->getString('crumb');
            if ($crumb && preg_match('#^(\d+)(\s+.*)?$#', trim($crumb), $ms)) {
                $level = intval($ms[1]);

                if (isset($ms[2])) {
                    $title = trim($ms[2]);
                } else {
                    $title = false;
                }

                if (!$title) {
                    $data  = $view->getData();
                    $title = isset($data['crumbTitle']) && $data['crumbTitle'] ? $data['crumbTitle'] : false;
                }

                if ($title) {
                    $breadCrumbs[ $level ] = [$title, Router::getFullURI()];
                    ksort($breadCrumbs);
                    $breadCrumbs              = array_slice($breadCrumbs, 0, $level + 1, true);
                    $_SESSION['_wula_bdCbs_'] = $breadCrumbs;
                }
            } else if ($breadCrumbs && $ann->has('crumbd')) {
                $ref = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
                if ($ref) {
                    foreach ($breadCrumbs as &$breadCrumb) {
                        if ($breadCrumb[1] == $ref) {
                            $breadCrumb[1] = url_append_args($breadCrumb[1], Router::getRouter()->queryParams);
                        }
                    }
                    $_SESSION['_wula_bdCbs_'] = $breadCrumbs;
                }
            }
            if ($crumb && $breadCrumbs) {
                $view->assign('breadCrumbs', $breadCrumbs);
            }
        }

        return $view;
    }
}