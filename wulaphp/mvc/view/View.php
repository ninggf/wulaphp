<?php

namespace wulaphp\mvc\view;

use wulaphp\io\Response;
use wulaphp\router\Router;

/**
 * 视图基类
 *
 * 用于定义模板的绘制和头部输出.
 *
 * @author  Guangfeng Ning <windywany@gmail.com> 2010-11-14 12:25
 * @version 1.0
 * @since   1.0
 * @package view
 */
abstract class View implements \ArrayAccess, Renderable {
    protected $tpl          = '';
    protected $data;
    protected $headers      = [];
    protected $sytles       = [];
    protected $scripts      = ['head' => [], 'foot' => []];
    protected $cache_expire = 0;
    protected $status       = 200;

    /**
     *
     * @param string|array $data
     * @param string       $tpl
     * @param array        $headers
     * @param int          $status
     */
    public function __construct($data = [], $tpl = '', $headers = [], $status = 200) {
        if (empty ($data)) {
            $this->tpl  = str_replace('/', DS, $tpl);
            $this->data = [];
        } else if (is_array($data)) {
            $this->tpl  = str_replace('/', DS, $tpl);
            $this->data = $data;
        } else if (is_string($data)) {
            $this->tpl  = str_replace('/', DS, $data);
            $this->data = [];
        }
        if (is_array($headers)) {
            $this->headers = $headers;
        }
        $this->setHeader();
        $this->status = $status;
    }

    public function offsetExists($offset) {
        return isset ($this->data [ $offset ]);
    }

    public function offsetGet($offset) {
        return $this->data [ $offset ];
    }

    public function offsetSet($offset, $value) {
        $this->data [ $offset ] = $value;
    }

    public function offsetUnset($offset) {
        unset ($this->data [ $offset ]);
    }

    /**
     * @param array|string $data
     * @param mixed|null   $value
     *
     * @return \wulaphp\mvc\view\View
     */
    public function assign($data, $value = null) {
        if (is_array($data)) {
            $this->data = array_merge($this->data, $data);
        } else if ($data) {
            $this->data [ $data ] = $value;
        }

        return $this;
    }

    public function getTemplate() {
        return $this->tpl;
    }

    public function setTemplate($tpl) {
        $this->tpl = $tpl;
    }

    /**
     * 设置缓存时间.
     *
     * @param int $expire 默认3600秒
     *
     * @return \wulaphp\mvc\view\View
     */
    public function expire($expire = 3600) {
        $this->cache_expire = intval($expire);
        defined('CACHE_EXPIRE') or define('CACHE_EXPIRE', $expire);

        return $this;
    }

    /**
     * 添加样式文件.
     *
     * @param string|array $file css file url
     *
     * @return \wulaphp\mvc\view\View
     */
    public function addStyle($file) {
        if (is_array($file)) {
            foreach ($file as $f) {
                if (!in_array($f, $this->sytles)) {
                    $this->sytles [] = $f;
                }
            }
        } else if (!in_array($file, $this->sytles)) {
            $this->sytles [] = $file;
        }

        return $this;
    }

    public function getStyles($view = null) {
        if ($view instanceof View) {
            $view->sytles = $this->sytles;
        }

        return $this->sytles;
    }

    /**
     * @param string $file script file url
     * @param bool   $foot
     *
     * @return \wulaphp\mvc\view\View
     */
    public function addScript($file, $foot = false) {
        if ($foot) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    if (!in_array($f, $this->scripts ['foot'])) {
                        $this->scripts ['foot'] [] = $f;
                    }
                }
            } else if (!in_array($file, $this->scripts ['foot'])) {
                $this->scripts ['foot'] [] = $file;
            }
        } else {
            if (is_array($file)) {
                foreach ($file as $f) {
                    if (!in_array($f, $this->scripts ['head'])) {
                        $this->scripts ['head'] [] = $f;
                    }
                }
            } else if (!in_array($file, $this->scripts ['head'])) {
                $this->scripts ['head'] [] = $file;
            }
        }

        return $this;
    }

    public function getScripts($type = null) {
        if ($type instanceof View) {
            $type->scripts = $this->scripts;
        }
        if ($type == 'foot') {
            return $this->scripts ['foot'];
        } else if ($type == 'head') {
            return $this->scripts ['head'];
        } else {
            return $this->scripts;
        }
    }

    public function getData() {
        return $this->data;
    }

    /**
     * set http response header
     */
    public function echoHeader() {
        if ($this->status != 200) {
            http_response_code($this->status);
        } else if (defined('CACHE_EXPIRE') && CACHE_EXPIRE) {
            Response::cache(CACHE_EXPIRE);
        }
        if (!empty ($this->headers) && is_array($this->headers)) {
            foreach ($this->headers as $name => $value) {
                @header("$name: $value", true);
            }
        }
    }

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * 输出下载头.
     *
     * @param string $fileName
     * @param int    $length
     * @param string $desc
     *
     * @return $this
     */
    public function download($fileName, $length = 0, $desc = null) {
        Response::nocache();
        $this->headers['Content-Type']        = Router::mimeContentType($fileName);
        $this->headers['Content-Disposition'] = 'attachment; filename="' . basename($fileName) . '"';
        if ($length) {
            $this->headers['Content-Length'] = $length;
        }
        if ($desc) {
            $this->headers['Content-Description'] = $desc;
        }

        return $this;
    }

    /**
     * 设置输出头
     */
    protected function setHeader() {
    }

    /**
     * 响应码.
     *
     * @param int $status
     *
     * @return $this
     */
    public function status($status) {
        $this->status = $status;

        return $this;
    }
}