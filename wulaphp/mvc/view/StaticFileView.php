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

use wulaphp\io\Response;
use wulaphp\router\Router;

/**
 * 静态资源视图。
 *
 * @package wulaphp\mvc\view
 */
class StaticFileView extends View {
    private $mtime;

    public function __construct($tpl = '') {
        $headers         = ['Content-Type' => Router::mimeContentType($tpl)];
        $this->mtime     = @filemtime($tpl);
        $headers['ETag'] = substr(md5_file($tpl), 0, 8) . '-' . substr(md5($this->mtime), 0, 4);
        parent::__construct([], $tpl, $headers);
    }

    public function echoHeader() {
        @header_remove('Set-Cookie');
        @header_remove('X-Powered-By');
        @header_remove('Expires');
        @header_remove('Pragma');
        @header_remove('Cache-Control');
        parent::echoHeader();
    }

    public function render() {
        $mtime = $this->mtime;
        $iftag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] == $this->headers['ETag'] : false;
        $mds   = isset ($_SERVER ['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER ['HTTP_IF_MODIFIED_SINCE']) == $mtime : false;
        if ($mtime && $iftag && $mds) {
            http_response_code(304);
            if (php_sapi_name() == 'cgi-fcgi') {
                @header('Status: 304 Not Modified');
            }
        } else {
            Response::lastModified($mtime);
            @header('Content-Length: ' . filesize($this->tpl));
            @readfile($this->tpl);
        }

        return '';
    }
}