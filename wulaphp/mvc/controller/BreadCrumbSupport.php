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

use wulaphp\io\Request;
use wulaphp\mvc\view\View;
use wulaphp\router\Router;
use wulaphp\util\Annotation;

/**
 * 基于session的面包屑特性。不支持同一个登录(SESSION)开多窗口（会导致导航错乱）。
 * 使用方法:
 *
 * 1. 在方法上添加crumb或crumbd注解
 * 2. crumb注解格式:
 *      2.1 @crumb group-1
 *          2.1.1 此时可以通过设置crumbTitle来自定义标题
 *      2.2 @crumb group-1 导航标题
 *      2.3 group 为分组
 * 3. 通过注解@keepArgs保存搜索条件
 *      3.1 @keepArgs myargs
 * 4. 通过注解@restoreArgs恢复搜索条件
 *      4.1 @restoreArgs myargs
 * @package wulaphp\mvc\controller
 */
trait BreadCrumbSupport {
    protected final function beforeRunInBreadCrumbSupport($method, $view) {
        /**@var Annotation $ann */
        $ann = $this->methodAnn;
        if (($id = $ann->getString('restoreArgs'))) {
            $args = sess_get('kags_' . $id, []);
            if ($args && is_array($args)) {
                Request::getInstance()->addUserData($args, false, true);
            }
        } else if (($id = $ann->getString('keepArgs'))) {
            $args = Router::getRouter()->queryParams;
            unset($args['_']);
            if ($args) {
                $_SESSION[ 'kags_' . $id ] = $args;
            }
        }

        return $view;
    }

    /**
     * 后运行.
     *
     * @param string                 $action
     * @param \wulaphp\mvc\view\View $view
     * @param \ReflectionMethod      $method
     *
     * @return \wulaphp\mvc\view\View
     */
    protected final function afterRunInBreadCrumbSupport($action, $view, $method) {
        /**@var Annotation $ann */
        $ann = $this->methodAnn;
        if ($view instanceof View) {
            $breadCrumbs = [];
            $crumb       = $ann->getString('crumb');
            if ($crumb && preg_match('#^([a-z][a-z_\d]*)-(\d+)(\s+.*)?$#i', trim($crumb), $ms)) {
                $sname       = '_wula_bdCbs_' . $ms[1];
                $breadCrumbs = sess_get($sname, []);
                $level       = intval($ms[2]);
                $data        = $view->getData();
                $title       = isset($data['crumbTitle']) && $data['crumbTitle'] ? $data['crumbTitle'] : false;
                if (!$title && isset($ms[3])) {
                    $title = trim($ms[3]);
                }
                if ($title) {
                    $breadCrumbs[ $level ] = [$title, Router::getFullURI()];
                    ksort($breadCrumbs);
                    $breadCrumbs        = array_slice($breadCrumbs, 0, $level + 1, true);
                    $_SESSION[ $sname ] = $breadCrumbs;
                }
            }

            if ($crumb && $breadCrumbs) {
                $view->assign('breadCrumbs', $breadCrumbs);
            }
        }

        return $view;
    }
}