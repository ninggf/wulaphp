<?php

namespace wulaphp\router;

/**
 * 解析后的URL信息.
 * Class UrlParsedInfo
 * @package wulaphp\router
 */
class UrlParsedInfo implements \ArrayAccess {
    public  $uri         = '';//URI，包括参数
    public  $url         = '';//URL
    public  $ogpath      = '';//未解码的请求路径
    public  $path        = '';//文件路径
    public  $name        = '';//文件名
    public  $ogname      = '';//未解码的请求文件名
    public  $ext         = '';//扩展名
    public  $page        = 1;//当前数码
    public  $total       = 1;//总页数
    public  $contentType = 'text/html;charset=UTF-8';
    public  $parsedUrl   = null;//解析后的URL
    private $params; //请求参数
    private $ogs         = [];
    private $urls        = [];
    private $pageData    = [];

    public function __construct($uri, $url, $params = []) {
        $this->url    = $url;
        $this->uri    = ltrim($uri, '/');
        $this->params = $params;

        $this->parseURL();
    }

    /**
     * 生成分页URL.
     *
     * @param string|int $page
     *
     * @return string
     */
    public function base($page) {
        if (!$this->urls) {
            $urls = [];
            if ($this->ogpath) {
                $urls [0] = implode('', [WWWROOT_DIR, $this->ogpath, '/', $this->ogname]);
            } else {
                $urls [0] = implode('', [WWWROOT_DIR, $this->ogname]);
            }
            $urls [1] = '';
            $suffix   = [];
            if ($this->ext) {
                $suffix [] = '.';
                $suffix [] = $this->ext;
            }
            if ($this->params) {
                $suffix [] = '?';
                $suffix [] = http_build_query($this->params, 'n');
            }
            if ($suffix) {
                $urls[2] = implode('', $suffix);
            }
            $this->urls = $urls;
        }
        if ($page > 1 || $page == 'all') {
            $this->urls [1] = '_' . $page;
        }
        $url            = implode('', $this->urls);
        $this->urls [1] = '';

        return $url;
    }

    /**
     * 重置所有数据到原始解析.
     */
    public function reset() {
        $this->page        = 1;
        $this->total       = 1;
        $this->urls        = [];
        $this->path        = $this->ogs[0];
        $this->ogpath      = $this->ogs[1];
        $this->name        = $this->ogs[2];
        $this->ogname      = $this->ogs[3];
        $this->ext         = $this->ogs[4];
        $this->contentType = 'text/html;charset=UTF-8';
        $this->pageData    = [];
    }

    /**
     * 设置当前URL对应的页面数据.
     *
     * @param array $data
     */
    public function setPageData($data) {
        $this->pageData = $data;
    }

    /**
     * 获取当前URL对应的页面数据.
     * @return array
     */
    public function getPageData() {
        return $this->pageData;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->uri;
    }

    public function offsetExists($offset) {
        if (is_numeric($offset)) {
            return true;
        }

        return isset($this->{$offset});
    }

    public function offsetGet($offset) {
        if (is_numeric($offset) || $offset == 'all') {
            return $this->base($offset);
        } else if (isset($this->{$offset})) {
            return $this->{$offset};
        }

        return null;
    }

    public function offsetSet($offset, $value) {
        //NOTHING TO DO
    }

    public function offsetUnset($offset) {
        //NOTHING TO DO
    }

    /**
     * 解析URL
     */
    protected function parseURL() {
        $cc     = explode('/', $this->uri);
        $chunks = [];
        foreach ($cc as $chunk) {
            $chunks[] = urldecode($chunk);
        }
        $name   = array_pop($chunks);
        $ogname = array_pop($cc);
        if ($chunks) {
            $this->path   = implode('/', $chunks);
            $this->ogpath = implode('/', $cc);
        }
        $names   = explode('.', $name);
        $ognames = explode('.', $ogname);
        if (count($names) > 1) {
            $this->ext  = array_pop($names);
            $this->name = implode('.', $names);
            array_pop($ognames);
            $this->ogname = implode('.', $ognames);
        } else {
            $this->name   = $name;
            $this->ogname = $ogname;
        }
        $this->ogs = [$this->path, $this->ogpath, $this->name, $this->ogname, $this->ext];
        if ($this->ext) {
            $this->contentType = Router::mimeContentType($this->url);
        }
        unset($chunks, $names, $cc, $ognames);
    }
}