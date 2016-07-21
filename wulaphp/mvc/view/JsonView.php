<?php
namespace wulaphp\mvc\view;

class JsonView extends View {

    /**
     *
     * @param array|string $data
     * @param array $headers
     */
    public function __construct($data, $headers = array()) {
        parent::__construct ( $data, '', $headers );
    }

    /**
     * 绘制
     *
     * @return string
     */
    public function render() {
        return json_encode ( $this->data );
    }

    public function setHeader() {
        @header ( 'Content-type: application/json', true );
    }
}
