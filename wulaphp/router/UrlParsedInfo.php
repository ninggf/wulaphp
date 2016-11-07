<?php
namespace wulaphp\router;
/**
 * 解析后的URL信息.
 * Class UrlParsedInfo
 * @package wulaphp\router
 */
class UrlParsedInfo {
	public $uri;
	public $url;
	public $path = '';
	public $name = '';
	public $ext  = '';
	public $page = 1;

	private $params;

	public function __construct($uri, $url, $params = []) {
		$this->url    = $url;
		$this->uri    = ltrim($uri, '/');
		$this->params = $params;

		$this->parseURL();
	}

	public function base($page) {
		static $urls = false;
		if ($urls === false) {
			$urls [0] = BASE_URL;
			$urls [1] = $this->path;
			$urls [2] = '/';
			$urls [3] = $this->name;
			$urls [4] = '';
			$urls [5] = '.';
			$urls [6] = $this->ext;
			if ($this->params) {
				$urls [7] = '?';
				$urls [8] = http_build_query($this->params, 'n');
			}
		}
		if ($page > 1) {
			$urls [4] = '_' . $page;
		}
		$url      = implode('', $urls);
		$urls [4] = '';

		return $url;
	}

	/**
	 * 解析URL
	 */
	protected function parseURL() {
		$chunks = explode('/', $this->url);
		$name   = array_pop($chunks);
		if ($chunks) {
			$this->path = implode('/', $chunks);
		}
		$names = explode('.', $name);
		if (count($names) > 1) {
			$this->ext  = array_pop($names);
			$this->name = implode('.', $names);
		} else {
			$this->name = $name;
		}
		unset($chunks, $names);
	}
}