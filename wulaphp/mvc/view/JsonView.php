<?php

namespace wulaphp\mvc\view;

class JsonView extends View {

    /**
     *
     * @param array|string $data
     * @param array        $headers
     * @param int          $status
     */
    public function __construct($data, $headers = [], $status = 200) {
        parent::__construct($data, '', $headers, $status);
    }

    /**
     * 绘制
     *
     * @return string
     */
    public function render() {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function setHeader() {
        $this->headers['Content-type'] = 'application/json';
    }
}
