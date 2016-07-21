<?php
namespace wulaphp\router;

class UrlParsedInfo {
    public $uri;
    public $url;
    public $path = '';
    public $name = '';
    public $ext = '';
    public $page = 1;
    private $host;
    private $port = '';
    private $protocal;
    private $params;

    public function __construct($uri, $url, $params) {
        $this->host = $_SERVER ['HTTP_HOST'];
        $this->protocal = isset ( $_SERVER ['HTTPS'] ) ? 'https://' : 'http://';
        $port = $_SERVER ['SERVER_PORT'];
        if (! (($this->protocal == 'http://' && $port == '80') || ($this->protocal == 'https://' && $port == '443'))) {
            $this->port = ':' . $port;
        }
        $this->url = $url;
        $this->uri = ltrim ( $uri, '/' );
        $this->params = $params;
    }

    public function full($page) {
        static $urls = false;
        if ($urls === false) {
            $urls [0] = $this->protocal;
            $urls [1] = $this->host;
            $urls [2] = '/';
            $urls [3] = $this->path;
            $urls [4] = '/';
            $urls [5] = $this->name;
            $urls [6] = '';
            $urls [7] = '.';
            $urls [8] = $this->ext;
            if ($this->params) {
                $urls [9] = '?';
                $urls [10] = http_build_query ( $this->params, 'n' );
            }
        }
        if ($page > 1) {
            $urls [6] = $this->getPager ( $page );
        }
        $url = implode ( '', $urls );
        $urls [6] = '';
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
                $urls [8] = http_build_query ( $this->params, 'n' );
            }
        }
        if ($page > 1) {
            $urls [4] = $this->getPager ( $page );
        }
        $url = implode ( '', $urls );
        $urls [4] = '';
    }

    protected function getPager($page) {
        return '_' . $page;
    }
}