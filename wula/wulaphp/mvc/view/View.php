<?php
namespace wulaphp\mvc\view;

/**
 * 视图基类
 *
 * 用于定义模板的绘制和头部输出.
 *
 * @author Guangfeng Ning <windywany@gmail.com> 2010-11-14 12:25
 * @version 1.0
 * @since 1.0
 * @package view
 */
abstract class View implements \ArrayAccess, Renderable {

    protected $tpl = '';

    protected $data;

<<<<<<< HEAD
=======
    protected $relatedPath;

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    protected $headers = array ();

    protected $sytles = array ();

    protected $scripts = array (
        'head' => array (),'foot' => array ()
    );

<<<<<<< HEAD
    protected $cache_expire = 0;
=======
    protected $title = null;
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87

    /**
     *
     * @param string|array $data
     * @param string $tpl
     * @param array $headers
     */
    public function __construct($data = array(), $tpl = '', $headers = array()) {
        if (empty ( $data )) {
            $this->tpl = str_replace ( '/', DS, $tpl );
            $this->data = array ();
        } else if (is_array ( $data )) {
            $this->tpl = str_replace ( '/', DS, $tpl );
            $this->data = $data;
        } else if (is_string ( $data )) {
            $this->tpl = str_replace ( '/', DS, $data );
            $this->data = array ();
        } else {
<<<<<<< HEAD
            trigger_error ( 'no template file!', E_USER_ERROR );
=======
            trigger_error ( 'no template file!' );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
        }
        
        if (is_array ( $headers )) {
            $this->headers = $headers;
        }
    }

    public function offsetExists($offset) {
        return isset ( $this->data [$offset] );
    }

    public function offsetGet($offset) {
        return $this->data [$offset];
    }

    public function offsetSet($offset, $value) {
        $this->data [$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset ( $this->data [$offset] );
    }

    public function assign($data, $value = null) {
        if (is_array ( $data )) {
            $this->data = array_merge_recursive ( $this->data, $data );
        } else if ($data) {
            $this->data [$data] = $value;
        }
    }

<<<<<<< HEAD
    public function expire($expire) {
        $this->cache_expire = intval ( $expire );
    }

=======
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    public function addStyle($file) {
        if (is_array ( $file )) {
            foreach ( $file as $f ) {
                if (! in_array ( $f, $this->sytles )) {
                    $this->sytles [] = $f;
                }
            }
        } else if (! in_array ( $file, $this->sytles )) {
            $this->sytles [] = $file;
        }
    }

    public function getStyles($view = null) {
        if ($view instanceof View) {
            $view->sytles = $this->sytles;
        }
        return $this->sytles;
    }

    public function addScript($file, $foot = false) {
        if ($foot) {
            if (is_array ( $file )) {
                foreach ( $file as $f ) {
                    if (! in_array ( $f, $this->scripts ['foot'] )) {
                        $this->scripts ['foot'] [] = $f;
                    }
                }
            } else if (! in_array ( $file, $this->scripts ['foot'] )) {
                $this->scripts ['foot'] [] = $file;
            }
        } else {
            if (is_array ( $file )) {
                foreach ( $file as $f ) {
                    if (! in_array ( $f, $this->scripts ['head'] )) {
                        $this->scripts ['head'] [] = $f;
                    }
                }
            } else if (! in_array ( $file, $this->scripts ['head'] )) {
                $this->scripts ['head'] [] = $file;
            }
        }
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
        if (! empty ( $this->headers ) && is_array ( $this->headers )) {
            foreach ( $this->headers as $name => $value ) {
                @header ( "$name: $value", true );
            }
        }
        $this->setHeader ();
    }
<<<<<<< HEAD
=======

    public function setRelatedPath($path) {
        if ($this->tpl && $this->tpl {0} == '@') {
            $this->tpl = substr ( $this->tpl, 1 );
        } else {
            $this->relatedPath = $path;
        }
    }

>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
    /**
     * 设置输出头
     */
    protected function setHeader() {
    }
}