<?php

namespace wulaphp\mvc\view;

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
	 *
	 * @throws \Exception
	 */
	public function __construct($data = array(), $tpl = '', $headers = array(), $status = 200) {
		if (empty ($data)) {
			$this->tpl  = str_replace('/', DS, $tpl);
			$this->data = array();
		} else if (is_array($data)) {
			$this->tpl  = str_replace('/', DS, $tpl);
			$this->data = $data;
		} else if (is_string($data)) {
			$this->tpl  = str_replace('/', DS, $data);
			$this->data = array();
		} else {
			throw new \Exception(__('Please give me a template file to render'));
		}

		if (is_array($headers)) {
			$this->headers = $headers;
		}
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
	 * @param array $data
	 * @param null  $value
	 *
	 * @return \wulaphp\mvc\view\View
	 */
	public function assign($data, $value = null) {
		if (is_array($data)) {
			$this->data = array_merge_recursive($this->data, $data);
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

	public function expire($expire) {
		$this->cache_expire = intval($expire);
	}

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
	}

	public function getStyles($view = null) {
		if ($view instanceof View) {
			$view->sytles = $this->sytles;
		}

		return $this->sytles;
	}

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
			status_header($this->status);
		}
		if (!empty ($this->headers) && is_array($this->headers)) {
			foreach ($this->headers as $name => $value) {
				@header("$name: $value", true);
			}
		}
		$this->setHeader();
	}

	/**
	 * 设置输出头
	 */
	protected function setHeader() {
	}
}