<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\view;

class XmlView extends View {
	protected $root = 'root';

	/**
	 *
	 * @param array|string $data
	 * @param string       $root
	 * @param array        $headers
	 * @param int          $status
	 */
	public function __construct($data, $root, $headers = [], $status = 200) {
		parent::__construct($data, '', $headers, $status);
		$this->root = $root;
	}

	/**
	 * 绘制
	 *
	 * @return string
	 */
	public function render() {
		if (extension_loaded('xml')) {
			$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $this->root . '/>');
			$this->addNode($xml, $this->data);

			return $xml->asXML();
		} else {
			return '<?xml version="1.0"><error>xml extension is not installed</error>';
		}
	}

	private function addNode(\SimpleXMLElement &$node, $data) {
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$nn = $node->addChild($k);
				$this->addNode($nn, $v);
			} else if ($k{0} == '@') {
				$node->addAttribute(substr($k, 1), $v);
			} else {
				$node->addChild($k, $v);
			}
		}
	}

	protected function setHeader() {
		$this->headers['Content-type'] = 'text/xml; charset=utf-8';
	}
}