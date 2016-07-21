<?php
namespace wulaphp\io;

interface IResponseAlter {

    function before_output_content($content);

    function filter_output_content($content);

    function after_content_output($content);
}

?>