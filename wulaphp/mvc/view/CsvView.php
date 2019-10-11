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
/**
 * csv 视图.
 *
 * @package wulaphp\mvc\view
 */
class CsvView extends View {
    private $fileName;
    private $heads   = null;
    private $sepChar = ',';

    /**
     *
     * @param array  $data
     * @param string $filename
     * @param int    $status
     */
    public function __construct(array $data, $filename = '', $status = 200) {
        parent::__construct($data, '', [], $status);
        $this->fileName = $filename;
    }

    public function render() {
        $csvData = [];
        if ($this->heads) {
            $csvData [] = implode($this->sepChar, $this->heads);
        }
        foreach ($this->data as $row) {
            $csvData[] = implode($this->sepChar, $row);
        }

        return implode("\n", $csvData);
    }

    /**
     * 设置头列.
     *
     * @param array $heads
     *
     * @return $this
     */
    public function withHeads(array $heads) {
        $this->heads = $heads;

        return $this;
    }

    /**
     * 设置分隔符.
     *
     * @param string $sep
     *
     * @return $this
     */
    public function sep($sep) {
        $this->sepChar = $sep;

        return $this;
    }

    protected function setHeader() {
        $this->headers['Content-type'] = 'text/csv; charset=utf-8';
        if ($this->fileName) {
            $this->headers['Content-Disposition'] = 'attachment; filename="' . $this->fileName . '.csv"';
        }
    }
}