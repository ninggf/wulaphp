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
    protected $fileName;

    /**
     *
     * @param array|string $data
     * @param string       $root
     * @param string       $filename
     */
    public function __construct($data, $root = 'root', $filename = '') {
        $this->root     = $root;
        $this->fileName = $filename;
        parent::__construct($data, '');
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
            return '<?xml version="1.0" encoding="UTF-8"><error>xml extension is not installed</error>';
        }
    }

    private function addNode(\SimpleXMLElement &$node, $data) {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                if (is_numeric($k)) {
                    $this->addNode($node, $v);
                } else {
                    if (array_key_exists('#', $v)) {
                        $nn = $node->addChild($k, $v['#']);
                        unset($v['#']);
                    } else {
                        $nn = $node->addChild($k);
                    }
                    $this->addNode($nn, $v);
                }
            } else if ($k{0} == '@') {
                $node->addAttribute(substr($k, 1), $v);
            } else {
                $node->addChild($k, $v);
            }
        }
    }

    protected function setHeader() {
        $this->headers['Content-type']        = 'text/xml; charset=utf-8';
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $this->fileName . '.xml"';
    }
}