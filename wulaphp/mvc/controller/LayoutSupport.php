<?php

namespace wulaphp\mvc\controller;

use wulaphp\mvc\view\View;
use wulaphp\router\DefaultDispatcher;

/**
 * Class LayoutSupport
 *
 * @property-read  string                  $layout 布局模板.
 * @property-read  \ReflectionClass        $reflectionObj
 * @property-read \wulaphp\util\Annotation $methodAnn
 * @package wulaphp\mvc\controller
 */
trait LayoutSupport {
    /**
     * @param string|array $tpl
     * @param array        $data
     *
     * @return \wulaphp\mvc\view\View
     */
    protected final function render(?string $tpl = null, array $data = []) {
        if ($this instanceof Controller) {
            if (is_array($tpl)) {
                $data = $tpl;
                $tpl  = null;
            }
            $ext = ($_SERVER['HTTP_PJAX'] ?? 0) ? '' : '.tpl';

            if ($tpl && $tpl[0] == '~') {
                $tpl = substr($tpl, 1);
                $tpl = $tpl . $ext;
            } else if ($tpl) {
                $path = str_replace(['\\', 'controllers'], [DS, 'views'], $this->reflectionObj->getNamespaceName());
                $tpl  = $path . DS . $tpl . $ext;
            } else {
                $path = str_replace(['\\', 'controllers'], [DS, 'views'], $this->reflectionObj->getNamespaceName());
                $tpl  = $path . DS . $this->ctrName . DS . $this->action . $ext;
            }

            if ($ext) {
                $layout = '~' . $this->layout;
                $data   = $this->onInitLayoutData($data);

                $data['workspaceView'] = $tpl;
                $view                  = view($layout, $data);
            } else {
                $view = view('~' . $tpl, $data);
            }

            return $view;
        }

        return null;
    }

    protected final function onInitLayoutSupport() {
        if ($this instanceof Controller) {
            if (!isset($this->layout)) {
                if ($this->reflectionObj instanceof \ReflectionClass) {
                    $ann          = $this->methodAnn;
                    $this->layout = $ann->getString('layout');
                } else {
                    $this->layout = null;
                }
            }
            if (!$this->layout) {
                $msg = __('The layout property of %s is not found', get_class($this));
                throw new \BadMethodCallException($msg);
            }
        } else {
            throw new \BadMethodCallException('LayoutSupport is not for ' . get_class($this));
        }
    }

    protected final function afterRunInLayoutSupport($action, $view, $method) {
        if ($view === null) {
            $view = $this->render($action);
            $ns   = implode('/', array_slice(explode('\\', $this->clzName), 0, - 2));
            DefaultDispatcher::prepareView($view, $ns, $this, $action);
        }
        if (isset($_SERVER['HTTP_PJAX']) && $view instanceof View) {
            $nan   = $this->methodAnn;
            $title = $nan->getString('title');
            if ($title) {
                $data  = $view->getData();
                $title = preg_replace_callback('#\$\{([^\}]+)\}#', function ($ms) use ($data) {
                    return $data[ $ms[1] ] ?? '';
                }, $title);
                if ($title) {
                    header('PageTitle: ' . html_escape($title), true);
                    $view->assign('pageTitle', $title);
                }
            }
        }

        return $view;
    }

    /**
     * 初始化Layout数据.
     *
     * @param array $data
     *
     * @return array
     */
    protected function onInitLayoutData(array $data): array {
        return $data;
    }
}